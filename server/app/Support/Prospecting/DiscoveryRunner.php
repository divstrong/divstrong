<?php

namespace App\Support\Prospecting;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\ProspectDiscoveryCandidate as Candidate;
use App\Models\ProspectDiscoveryRun as Run;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Drives a discovery run from "Get Some!" to prospects in the book.
 *
 * Built as a resumable state machine rather than one long job because these installs run
 * QUEUE_CONNECTION=sync with no worker process (see SendMarketingCampaignJob, dispatched with a
 * direct ->handle()). Fifty agencies researched, their contact pages fetched and their addresses
 * probed is minutes of work — far past any web request's execution limit.
 *
 * So each advance() does one bounded unit and returns, the run row remembers where it got to,
 * and the list page polls. Which also gives the progress bar for free: the same row the poll
 * updates is the row the bar reads.
 *
 * Every stage is idempotent per candidate, so a browser closed mid-run leaves a resumable run
 * rather than a half-imported mess.
 */
class DiscoveryRunner
{
    public function __construct(
        private readonly CompanySource $discovery,
        private readonly PageHarvester $harvester,
        private readonly EmailVerifier $verifier,
        private readonly NameFinder $names = new NameFinder,
    ) {}

    public static function make(): self
    {
        return new self(
            static::source(),
            PageHarvester::fromConfig(),
            EmailVerifier::fromConfig(),
        );
    }

    /**
     * The configured company source.
     *
     * Places by preference — it enumerates the same businesses for a fraction of a cent that
     * the model establishes for six figures of tokens. The model stays available for when
     * owner names matter more than cost, since Places has no owner field at all.
     */
    public static function source(?string $choice = null): CompanySource
    {
        $places = PlacesDiscovery::fromConfig();
        $model = ContactDiscovery::fromConfig();

        // A run's own choice wins over the installation default: the two sources trade cost
        // against owner-name coverage very differently, and which trade is right depends on the
        // batch, not on the server.
        return match ($choice ?: config('prospecting.source', 'auto')) {
            self::VIA_PLACES => $places,
            self::VIA_MODEL => $model,
            // Fall through to the model rather than failing when no Places key is set.
            default => $places->isConfigured() ? $places : $model,
        };
    }

    /** Google Places: pennies per hundred companies, roughly one principal's name in four. */
    public const VIA_PLACES = 'places';

    /** Claude web search: finds principals by name in local press and directories, costs tokens. */
    public const VIA_MODEL = 'model';

    /** Whatever the server is configured for. */
    public const VIA_AUTO = 'auto';

    /** Where a run stashes its source choice. Not the `source` column — that is the lead tag. */
    public const CRITERIA_KEY = 'company_source';

    /** A runner wired to whichever source this particular run asked for. */
    public static function forRun(Run $run): self
    {
        return new self(
            static::source($run->criteria[self::CRITERIA_KEY] ?? null),
            PageHarvester::fromConfig(),
            EmailVerifier::fromConfig(),
        );
    }

    /**
     * The selectable sources, labelled with the trade-off that decides between them.
     *
     * @return array<string, string>
     */
    public static function sourceOptions(): array
    {
        return [
            self::VIA_PLACES => 'Google Places directory',
            self::VIA_MODEL => 'Web search (Claude)',
        ];
    }

    /** @return array<string, string> */
    public static function sourceDescriptions(): array
    {
        return [
            self::VIA_PLACES => 'Cheapest by far — a few cents per hundred agencies, no AI '
                .'tokens. Lists real businesses by category and city, but publishes no names, so '
                .'only about one agency in four ends up with someone to address.',
            self::VIA_MODEL => 'Finds the founder or principal by name from local business press, '
                .'directories and association listings, so roughly four agencies in five are '
                .'addressable. Costs around 106,000 tokens per dozen agencies.',
        ];
    }

    /**
     * Open a run. Does no work — the first advance() does.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function start(int $targetCount, ?string $source, ?int $assignedTo, ?int $requestedBy, array $criteria = []): Run
    {
        return Run::create([
            'requested_by' => $requestedBy,
            'assigned_to' => $assignedTo,
            'source' => $source,
            'target_count' => max(1, min(200, $targetCount)),
            'criteria' => $criteria,
            'status' => Run::STATUS_PENDING,
            'stage_message' => 'Queued',
        ]);
    }

    /**
     * Do one bounded unit of work and return the run.
     *
     * Never throws: a run that fails records why and stops, because the caller is a poll from a
     * table page and an exception there is a broken screen rather than a message.
     */
    public function advance(Run $run): Run
    {
        if ($run->isFinished()) {
            return $run;
        }

        try {
            return match ($run->status) {
                Run::STATUS_PENDING => $this->beginDiscovery($run),
                Run::STATUS_DISCOVERING => $this->discoverRound($run),
                Run::STATUS_VERIFYING => $this->verifyBatch($run),
                Run::STATUS_IMPORTING => $this->importAccepted($run),
                default => $run,
            };
        } catch (ProspectingException $e) {
            return $this->fail($run, $e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Prospect discovery run failed', [
                'run' => $run->id,
                'status' => $run->status,
                'error' => $e->getMessage(),
            ]);

            return $this->fail($run, 'Unexpected error: '.$e->getMessage());
        }
    }

    private function beginDiscovery(Run $run): Run
    {
        if (! $this->discovery->isConfigured()) {
            return $this->fail($run, $this->discovery instanceof PlacesDiscovery
                ? ProspectingException::missingPlacesKey()->getMessage()
                : ProspectingException::missingApiKey()->getMessage());
        }

        $run->update([
            'status' => Run::STATUS_DISCOVERING,
            'started_at' => now(),
            'stage_message' => 'Searching '.$this->discovery->name().' for agencies…',
        ]);

        return $run;
    }

    /**
     * Research one region/discipline combination and bank the agencies it finds.
     */
    private function discoverRound(Run $run): Run
    {
        $maxRounds = (int) config('prospecting.max_rounds', 6);

        // Over-fetch: plenty of agencies publish no named address, so the candidate pool has to
        // be bigger than the target or the run runs out of material before it runs out of
        // rounds.
        $poolTarget = (int) ceil($run->target_count * (float) config('prospecting.pool_multiplier', 2.5));

        if ($run->companies_found >= $poolTarget || $run->round >= $maxRounds) {
            return $this->beginVerifying($run);
        }

        /*
         * Rounds are issued several at a time.
         *
         * One web-search-backed round takes the better part of a minute, and a 50-prospect run
         * needs ten of them. In series that is a ten-minute wait for a button; in parallel it
         * is closer to one, because the rounds are independent by construction — each covers a
         * different region and speciality.
         *
         * Capped per round rather than divided evenly: a round asked for twenty-odd companies
         * runs into the output limit and comes back truncated, which is what broke a live run.
         * Smaller rounds, more of them, at the same wall-clock cost.
         */
        $perRound = min(
            (int) config('prospecting.per_round_cap', 12),
            max(5, (int) ceil($poolTarget / $maxRounds)),
        );

        $concurrency = max(1, (int) config('prospecting.round_concurrency', 3));

        $batch = range($run->round, min($run->round + $concurrency, $maxRounds) - 1);

        $result = $this->discovery->discoverRounds(
            rounds: $batch,
            perRound: $perRound,
            criteria: $run->criteria ?? [],
            excludeDomains: $this->knownDomains(),
        );

        // A failed round is logged and skipped. Only a batch where every round failed is worth
        // stopping the run for — otherwise one rate-limited request would bin nine good ones.
        if ($result['companies'] === [] && $result['errors'] !== []) {
            if (count($result['errors']) >= count($batch)) {
                throw new ProspectingException(implode('; ', array_slice($result['errors'], 0, 3)));
            }
        }

        foreach ($result['errors'] as $error) {
            Log::warning('Prospect discovery round failed', ['run' => $run->id, 'error' => $error]);
        }

        // Recorded per run because search cost is not something anyone can eyeball: the whole
        // accumulated context is re-billed on every step of the search loop, so it scales with
        // the square of the search budget rather than with agencies found.
        $usage = $result['usage'] ?? ['input' => 0, 'output' => 0, 'searches' => 0];

        $run->increment('tokens_in', $usage['input']);
        $run->increment('tokens_out', $usage['output']);
        $run->increment('searches', $usage['searches']);

        $added = 0;

        foreach ($result['companies'] as $company) {
            if ($this->recordCandidate($run, $company)) {
                $added++;
            }
        }

        $run->increment('round', count($batch));
        $run->increment('companies_found', $added);

        $run->refresh()->update([
            'stage_message' => "Found {$run->companies_found} agencies across {$run->round} searches…",
        ]);

        // Nothing new twice running means the well is dry for this criteria; verify what we have
        // rather than burning the remaining rounds.
        if ($added === 0 && $run->round >= 2) {
            return $this->beginVerifying($run->refresh());
        }

        return $run->refresh();
    }

    /**
     * Store one company, unless we already have it.
     *
     * @param  array<string, mixed>  $company
     */
    private function recordCandidate(Run $run, array $company): bool
    {
        $host = $this->harvester->hostOf($company['website'] ?? null);

        // Same agency twice inside one run — different searches surface the same business often.
        if ($host !== null) {
            $duplicate = Candidate::where('run_id', $run->id)
                ->where('website', 'like', '%'.$host.'%')
                ->exists();

            if ($duplicate) {
                return false;
            }
        } elseif (filled($company['company'] ?? null)) {
            $duplicate = Candidate::where('run_id', $run->id)
                ->where('company', $company['company'])
                ->exists();

            if ($duplicate) {
                return false;
            }
        }

        Candidate::create([
            'run_id' => $run->id,
            'company' => $company['company'] ?? null,
            'contact_name' => $company['contact_name'] ?? null,
            'title' => $company['title'] ?? null,
            'phone' => $this->cleanPhone($company['phone'] ?? null),
            'website' => $company['website'] ?? null,
            'city' => $company['city'] ?? null,
            'state' => $company['state'] ?? null,
            'status' => Candidate::STATUS_PENDING,
            'checks' => [
                'contact_page_url' => $company['contact_page_url'] ?? null,
                'email_hint' => $company['email_hint'] ?? null,
                'email_source_url' => $company['email_source_url'] ?? null,
                'specialties' => $company['specialties'] ?? null,
            ],
        ]);

        return true;
    }

    private function beginVerifying(Run $run): Run
    {
        $run->update([
            'status' => Run::STATUS_VERIFYING,
            'stage_message' => 'Reading contact pages…',
        ]);

        return $run;
    }

    /**
     * Harvest and verify as many candidates as fit in one request's time budget.
     *
     * Bounded by wall clock rather than a fixed count: one agency might answer in 200ms and the
     * next might have four slow pages and a mail server that tarpits. A count would make the
     * slow case time the request out.
     */
    private function verifyBatch(Run $run): Run
    {
        if ($run->accepted >= $run->target_count) {
            return $this->beginImporting($run);
        }

        $budget = (float) config('prospecting.batch_seconds', 15);
        $maxPerBatch = (int) config('prospecting.batch_size', 6);
        $deadline = microtime(true) + $budget;

        $pending = Candidate::where('run_id', $run->id)
            ->where('status', Candidate::STATUS_PENDING)
            ->orderBy('id')
            ->limit($maxPerBatch)
            ->get();

        if ($pending->isEmpty()) {
            // Out of candidates. More rounds available? Go back and find more.
            $maxRounds = (int) config('prospecting.max_rounds', 6);

            if ($run->accepted < $run->target_count && $run->round < $maxRounds) {
                $run->update([
                    'status' => Run::STATUS_DISCOVERING,
                    'stage_message' => 'Looking for more agencies…',
                ]);

                return $run;
            }

            return $this->beginImporting($run);
        }

        foreach ($pending as $candidate) {
            $this->processCandidate($run, $candidate);

            if (microtime(true) >= $deadline) {
                break;
            }

            if ($run->refresh()->accepted >= $run->target_count) {
                break;
            }
        }

        $run->refresh()->update([
            'stage_message' => "Verified {$run->accepted} deliverable of "
                .($run->accepted + $run->rejected).' checked…',
        ]);

        return $run->refresh();
    }

    /**
     * Take one agency from "found on the web" to accepted or rejected, with the reason recorded.
     */
    private function processCandidate(Run $run, Candidate $candidate): void
    {
        $checks = $candidate->checks ?? [];

        $extraUrls = array_values(array_filter([
            $checks['contact_page_url'] ?? null,
            $checks['email_source_url'] ?? null,
        ]));

        $harvest = $this->harvester->harvest(
            website: $candidate->website,
            contactName: $candidate->contact_name,
            extraUrls: $extraUrls,
        );

        $checks['pages_read'] = $harvest['pages'];
        $checks['addresses_on_site'] = array_slice($harvest['seen'], 0, 12);

        // Did the model invent the address it offered? Recorded either way — it is the only
        // running measure of whether the "do not guess" instruction is holding.
        if (filled($checks['email_hint'] ?? null)) {
            $checks['hint_confirmed'] = in_array(
                strtolower($checks['email_hint']),
                array_map('strtolower', $harvest['seen']),
                true,
            );
        }

        if ($harvest['email'] === null) {
            $candidate->update(['checks' => $checks]);

            // Either the site publishes no address at all, or the only ones on it are
            // noreply@/careers@ boxes that no sales email should ever go to.
            $candidate->reject(
                $harvest['seen'] === [] ? 'no_address_published' : 'only_unusable_addresses',
            );
            $run->increment('rejected');

            return;
        }

        $email = $harvest['email'];

        // Whether we reached a named person or settled for the front desk. Carried onto the
        // prospect so whoever writes the email knows which one they are addressing.
        $shared = ($harvest['kind'] ?? null) === PageHarvester::KIND_SHARED;
        $checks['address_kind'] = $harvest['kind'];

        // Counted before the gates, so "found an address" stays distinguishable from "found a
        // usable one" — the gap between the two is what says whether the bottleneck is
        // discovery or verification.
        $run->increment('emails_harvested');

        if ($this->alreadyInBook($email)) {
            $candidate->update(['email' => $email, 'source_url' => $harvest['source_url'], 'checks' => $checks]);
            $candidate->reject('already_a_prospect');
            $run->increment('rejected');

            return;
        }

        if ($this->duplicateInRun($run, $email, $candidate->id)) {
            $candidate->update(['email' => $email, 'source_url' => $harvest['source_url'], 'checks' => $checks]);
            $candidate->reject('duplicate_in_run');
            $run->increment('rejected');

            return;
        }

        /*
         * Who do we address this to?
         *
         * Google Places has no owner field, so for the cheap path the name has to come off the
         * agency's own page — which NameFinder can usually do, because a studio that publishes
         * dana@ generally also says "Dana Ruiz, Founder" on its team page.
         *
         * Without a name there is nobody to write to, and "Hi there" to a stranger reads as
         * bulk mail in the first three words. So an unaddressable lead is rejected rather than
         * imported to sit in the book unusable.
         */
        $found = $this->names->find(
            known: $candidate->contact_name,
            email: $email,
            html: $harvest['source_html'] ?? null,
            company: $candidate->company,
        );

        // The address page is usually /contact: a form and a phone number, and nobody's name.
        // If it yielded nothing, read the pages that actually introduce people.
        if (blank($found['name'])) {
            $found = $this->names->findOnSite(
                harvester: $this->harvester,
                website: $candidate->website,
                email: $email,
                company: $candidate->company,
                alreadyRead: $harvest['source_url'] ?? null,
            );
        }

        $checks['name_source'] = $found['source'];

        if (filled($found['name'])) {
            $candidate->contact_name = $found['name'];

            if (blank($candidate->title) && filled($found['title'])) {
                $candidate->title = $found['title'];
            }
        }

        if (blank($candidate->contact_name) && config('prospecting.require_contact_name', true)) {
            $candidate->update([
                'email' => $email,
                'source_url' => $harvest['source_url'],
                'checks' => $checks,
            ]);
            $candidate->reject('no_contact_name');
            $run->increment('rejected');

            return;
        }

        $verdict = $this->verifier->verify($email, allowShared: $shared);

        $candidate->update([
            'contact_name' => $candidate->contact_name,
            'title' => $candidate->title,
            'email' => $email,
            'source_url' => $harvest['source_url'],
            'status' => Candidate::STATUS_HARVESTED,
            'checks' => array_merge($checks, $verdict['checks']),
        ]);

        if (! $verdict['ok']) {
            $candidate->reject($verdict['reason'] ?? 'failed_verification');
            $run->increment('rejected');

            return;
        }

        $candidate->update(['status' => Candidate::STATUS_ACCEPTED]);
        $run->increment('accepted');
    }

    private function beginImporting(Run $run): Run
    {
        $run->update([
            'status' => Run::STATUS_IMPORTING,
            'stage_message' => 'Adding to prospects…',
        ]);

        return $run;
    }

    /**
     * Write the survivors into the book.
     */
    private function importAccepted(Run $run): Run
    {
        $accepted = Candidate::where('run_id', $run->id)
            ->where('status', Candidate::STATUS_ACCEPTED)
            ->orderBy('id')
            ->limit(25)
            ->get();

        foreach ($accepted as $candidate) {
            // Re-checked at the last moment: a concurrent import or a second run could have
            // taken this address since it was accepted.
            if ($this->alreadyInBook($candidate->email)) {
                $candidate->reject('already_a_prospect');
                $run->increment('rejected');
                $run->decrement('accepted');

                continue;
            }

            $prospect = Prospect::create([
                'name' => $candidate->contact_name,
                'company' => $candidate->company,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'website' => $candidate->website,
                'city' => $candidate->city,
                'state' => $candidate->state,
                'title' => $candidate->title,
                'source' => $run->source,
                'assigned_to' => $run->assigned_to,
                // Everything discovery finds is an agency by construction — both sources
                // search for creative and media firms and nothing else — so the Agency
                // Intro is the right default email on the row. A rep who learns otherwise
                // on the phone can change it; the field is a hint, not a lock.
                'segment' => Prospect::SEGMENT_AGENCY,
                'lead_status' => Prospect::LEAD_UNQUALIFIED,
                'notes' => $this->provenanceNote($candidate),
            ]);

            $candidate->update([
                'status' => Candidate::STATUS_IMPORTED,
                'prospect_id' => $prospect->id,
            ]);

            ProspectActivity::record([
                'prospect_id' => $prospect->id,
                'type' => ProspectActivity::NOTE,
                'user_id' => $run->requested_by,
                'description' => 'Found by automated discovery — address read from '
                    .($candidate->source_url ?? 'the company website'),
                'meta' => [
                    'discovery_run' => $run->id,
                    'source_url' => $candidate->source_url,
                    'checks' => $candidate->checks,
                ],
            ]);

            $run->increment('imported');
        }

        $remaining = Candidate::where('run_id', $run->id)
            ->where('status', Candidate::STATUS_ACCEPTED)
            ->exists();

        if ($remaining) {
            return $run->refresh();
        }

        return $this->complete($run->refresh());
    }

    private function complete(Run $run): Run
    {
        $run->update([
            'status' => Run::STATUS_COMPLETE,
            'finished_at' => now(),
            'stage_message' => "Added {$run->imported} prospects "
                ."from {$run->companies_found} agencies checked",
        ]);

        return $run;
    }

    private function fail(Run $run, string $message): Run
    {
        $run->update([
            'status' => Run::STATUS_FAILED,
            'error' => Str::limit($message, 1000),
            'finished_at' => now(),
            'stage_message' => 'Failed',
        ]);

        return $run;
    }

    public function cancel(Run $run): Run
    {
        if ($run->isFinished()) {
            return $run;
        }

        $run->update([
            'status' => Run::STATUS_CANCELLED,
            'finished_at' => now(),
            'stage_message' => "Cancelled after importing {$run->imported}",
        ]);

        return $run;
    }

    /**
     * A short note on the record saying where the address came from.
     *
     * Written onto the prospect rather than left in the candidate table because the person
     * about to email a stranger is the one who needs to know how we got their address.
     */
    private function provenanceNote(Candidate $candidate): string
    {
        $checks = $candidate->checks ?? [];

        $lines = ['Found by automated discovery on '.now()->format('M j, Y').'.'];

        // Said first and plainly. Opening "Hi Lori" to a shared inbox reads badly, and the rep
        // writing the email is the person who needs to know which of the two this is.
        $lines[] = ($checks['address_kind'] ?? null) === PageHarvester::KIND_SHARED
            ? 'SHARED INBOX — no named address was published on their site. Address the agency, not a person.'
            : 'Personal address for a named contact.';

        if ($candidate->source_url) {
            $lines[] = 'Email published at: '.$candidate->source_url;
        }

        if (filled($checks['specialties'] ?? null)) {
            $lines[] = 'Services: '.$checks['specialties'];
        }

        $smtp = $checks['smtp'] ?? null;

        $lines[] = match ($smtp) {
            EmailVerifier::PASS => 'Mailbox confirmed by the receiving mail server.',
            EmailVerifier::FAIL => 'Mailbox check failed.',
            default => 'Mailbox not independently confirmed ('
                .($checks['smtp_detail'] ?? 'no result').') — address was published on their own site.',
        };

        if (($checks['catch_all'] ?? null) === true) {
            $lines[] = 'Their domain accepts mail for any address, so the mailbox check proves nothing.';
        }

        return implode("\n", $lines);
    }

    /** Domains already represented in the book, so discovery does not re-find them. */
    private function knownDomains(): array
    {
        return Prospect::query()
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->orderByDesc('id')
            ->limit(400)
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim(substr(strrchr($email, '@') ?: '', 1))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function alreadyInBook(string $email): bool
    {
        return Prospect::whereRaw('lower(email) = ?', [strtolower($email)])->exists();
    }

    private function duplicateInRun(Run $run, string $email, int $exceptId): bool
    {
        return Candidate::where('run_id', $run->id)
            ->where('id', '!=', $exceptId)
            ->whereRaw('lower(email) = ?', [strtolower($email)])
            ->whereIn('status', [
                Candidate::STATUS_ACCEPTED,
                Candidate::STATUS_IMPORTED,
                Candidate::STATUS_HARVESTED,
            ])
            ->exists();
    }

    private function cleanPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        return Str::limit(preg_replace('/[^0-9+().\- ]/', '', $phone), 40, '');
    }

    /**
     * Human-readable tally of why candidates were dropped, for the completion notice.
     *
     * @return array<string, int>
     */
    public static function rejectionBreakdown(Run $run): array
    {
        return Candidate::where('run_id', $run->id)
            ->where('status', Candidate::STATUS_REJECTED)
            ->selectRaw('reject_reason, count(*) as n')
            ->groupBy('reject_reason')
            ->pluck('n', 'reject_reason')
            ->all();
    }

    /** Plain-English label for a rejection reason. */
    public static function reasonLabel(?string $reason): string
    {
        return match ($reason) {
            'no_address_published' => 'no email published',
            'no_contact_name' => 'nobody named to address it to',
            'only_unusable_addresses' => 'only noreply@/careers@ style addresses',
            'already_a_prospect' => 'already in your book',
            'duplicate_in_run' => 'duplicate within this run',
            'role_account' => 'shared mailbox, not a person',
            'invalid_syntax' => 'malformed address',
            'disposable_domain' => 'disposable address',
            'no_mx_record' => 'domain does not accept mail',
            'mailbox_not_found' => 'mailbox rejected by their server',
            default => $reason ?? 'unknown',
        };
    }
}
