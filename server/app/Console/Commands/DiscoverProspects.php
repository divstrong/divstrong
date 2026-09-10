<?php

namespace App\Console\Commands;

use App\Models\ProspectDiscoveryCandidate as Candidate;
use App\Models\ProspectDiscoveryRun as Run;
use App\Models\User;
use App\Support\Prospecting\DiscoveryRunner;
use Illuminate\Console\Command;

/**
 * Run prospect discovery from the console.
 *
 * The same runner the "Find Prospects" button drives, without a browser in the loop. Preferable for
 * anything large: a console process has no execution limit to trip over and no tab that can be
 * closed halfway through. The button is the convenient path; this is the reliable one.
 *
 *   php artisan prospects:discover --count=50 --source="Discovery Sep" --assign=3
 *   php artisan prospects:discover --resume=12
 *   php artisan prospects:discover --show=12
 */
class DiscoverProspects extends Command
{
    protected $signature = 'prospects:discover
        {--count=25 : How many verified prospects to find}
        {--source= : Source tag applied to everything imported}
        {--assign= : User id to assign the prospects to}
        {--region= : Restrict to one region, otherwise rounds rotate}
        {--notes= : Extra targeting passed to the search}
        {--via= : Where to look: places (cheap, fewer names) or model (tokens, more names)}
        {--resume= : Continue an existing run by id}
        {--show= : Print the outcome of a run and exit}';

    protected $description = 'Find creative and media agencies and import the ones with a verified, named email';

    public function handle(): int
    {
        if ($runId = $this->option('show')) {
            return $this->showRun((int) $runId);
        }

        $runner = DiscoveryRunner::make();

        $run = $this->option('resume')
            ? Run::find((int) $this->option('resume'))
            : $this->startRun($runner);

        if (! $run) {
            $this->error('Run not found.');

            return self::FAILURE;
        }

        if ($run->isFinished()) {
            $this->warn("Run {$run->id} already finished ({$run->status}).");

            return $this->showRun($run->id);
        }

        $this->info("Run {$run->id}: looking for {$run->target_count} prospects.");
        $this->line('');

        $bar = $this->output->createProgressBar(100);
        $bar->setFormat(' %bar% %percent:3s%%  %message%');
        $bar->setMessage('starting');
        $bar->start();

        // A generous ceiling rather than while(true): a bug in the state machine should end the
        // command, not spin against somebody's mail server all night.
        $maxSteps = 400;

        // Rebuilt from the run so --via is honoured, not the server default.
        $runner = DiscoveryRunner::forRun($run);

        for ($step = 0; $step < $maxSteps && $run->isActive(); $step++) {
            $run = $runner->advance($run);

            $bar->setProgress($run->progress());
            $bar->setMessage((string) $run->stage_message);
        }

        $bar->setProgress(100);
        $bar->finish();

        $this->line('');
        $this->line('');

        if ($run->isActive()) {
            $this->warn("Stopped after {$maxSteps} steps with the run still active. Resume with --resume={$run->id}.");

            return self::FAILURE;
        }

        return $this->showRun($run->id);
    }

    private function startRun(DiscoveryRunner $runner): Run
    {
        $assign = $this->option('assign');

        if ($assign !== null && ! User::find((int) $assign)) {
            $this->warn("No user with id {$assign}; importing unassigned.");
            $assign = null;
        }

        return $runner->start(
            targetCount: (int) $this->option('count'),
            source: $this->option('source') ?: 'Discovery '.now()->format('M j'),
            assignedTo: $assign !== null ? (int) $assign : null,
            requestedBy: null,
            criteria: array_filter([
                'region' => $this->option('region'),
                'notes' => $this->option('notes'),
                DiscoveryRunner::CRITERIA_KEY => $this->option('via'),
            ]),
        );
    }

    /**
     * Print what a run produced, and what it discarded.
     *
     * The rejection table is the part worth reading. A run that imports 12 of 50 looks broken
     * until it says that 20 agencies publish no named address at all.
     */
    private function showRun(int $runId): int
    {
        $run = Run::find($runId);

        if (! $run) {
            $this->error("Run {$runId} not found.");

            return self::FAILURE;
        }

        if ($run->status === Run::STATUS_FAILED) {
            $this->error('Failed: '.$run->error);

            return self::FAILURE;
        }

        $this->info("Run {$run->id} — {$run->status} — via "
            .DiscoveryRunner::source($run->criteria[DiscoveryRunner::CRITERIA_KEY] ?? null)->name());

        $this->table(['', ''], [
            ['Agencies researched', $run->companies_found],
            ['Search rounds', $run->round],
            ['Web searches', $run->searches],
            ['Verified addresses', $run->accepted],
            ['Imported', $run->imported],
            ['Rejected', $run->rejected],
            // Per run and per prospect, because the per-prospect number is the one that says
            // whether the search budget is set sensibly.
            ['Tokens in', number_format($run->tokens_in)],
            ['Tokens out', number_format($run->tokens_out)],
            ['Tokens per prospect', $run->imported > 0
                ? number_format(($run->tokens_in + $run->tokens_out) / $run->imported)
                : '—'],
        ]);

        $breakdown = DiscoveryRunner::rejectionBreakdown($run);

        if ($breakdown !== []) {
            $this->line('Why the rest were skipped:');
            $this->table(
                ['Reason', 'Count'],
                collect($breakdown)
                    ->sortDesc()
                    ->map(fn (int $n, string $reason) => [DiscoveryRunner::reasonLabel($reason), $n])
                    ->values()
                    ->all(),
            );
        }

        $imported = Candidate::where('run_id', $run->id)
            ->where('status', Candidate::STATUS_IMPORTED)
            ->get(['contact_name', 'company', 'email', 'source_url', 'checks']);

        // Where the names came from. On the Places path this is the number to watch: Places
        // supplies none, so every name here was recovered from the shop's own site.
        $nameSources = $imported
            ->map(fn (Candidate $c) => $c->checks['name_source'] ?? 'unknown')
            ->countBy();

        if ($nameSources->isNotEmpty()) {
            $this->line('Contact names came from: '
                .$nameSources->map(fn ($n, $src) => "{$n} {$src}")->implode(', '));
        }

        if ($imported->isNotEmpty()) {
            $this->line('Imported:');
            $this->table(
                ['Name', 'Company', 'Email', 'Found on'],
                $imported->map(fn (Candidate $c) => [
                    $c->contact_name ?? '—',
                    \Illuminate\Support\Str::limit($c->company ?? '—', 28),
                    $c->email,
                    \Illuminate\Support\Str::limit($c->source_url ?? '—', 44),
                ])->all(),
            );
        }

        // How often the model volunteered an address, and how often that address was actually
        // on the page. The running check on whether "do not guess" is holding.
        $hinted = Candidate::where('run_id', $run->id)
            ->whereNotNull('checks')
            ->get()
            ->filter(fn (Candidate $c) => array_key_exists('hint_confirmed', $c->checks ?? []));

        if ($hinted->isNotEmpty()) {
            $confirmed = $hinted->filter(fn (Candidate $c) => $c->checks['hint_confirmed'] === true)->count();

            $this->line(sprintf(
                'Model-supplied addresses: %d offered, %d confirmed on the page (%d not found there).',
                $hinted->count(),
                $confirmed,
                $hinted->count() - $confirmed,
            ));
        }

        return self::SUCCESS;
    }
}
