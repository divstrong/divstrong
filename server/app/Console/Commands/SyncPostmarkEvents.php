<?php

namespace App\Console\Commands;

use App\Http\Controllers\PostmarkWebhookController;
use App\Models\ProspectActivity;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Pulls opens and clicks from Postmark's API and timelines any the webhook missed.
 *
 * The webhook is the live path; this is for catching up — events from before the webhook
 * existed, or from while it was failing. Each event goes through the webhook's own
 * record(), so attribution, label repair and de-duplication are identical, and running it
 * twice records nothing new.
 *
 * Only replays an event onto a prospect this database actually emailed at that address:
 * Postmark's prospect id belongs to whichever environment sent the mail, and run from a
 * local copy it would otherwise pin live events onto unrelated local rows.
 */
class SyncPostmarkEvents extends Command
{
    protected $signature = 'prospects:sync-postmark {--days=30 : How far back to look}';

    protected $description = 'Backfill email opens and clicks from the Postmark API';

    private const PAGE = 500;

    public function handle(PostmarkWebhookController $recorder): int
    {
        $token = config('services.postmark.key')
            ?: (str_contains((string) config('mail.mailers.smtp.host'), 'postmarkapp.com')
                ? config('mail.mailers.smtp.username')
                : null);

        if (blank($token)) {
            $this->error('No Postmark server token: set POSTMARK_API_KEY.');

            return self::FAILURE;
        }

        $since = now()->subDays((int) $this->option('days'));
        $totals = [];

        foreach (['opens' => 'Opens', 'clicks' => 'Clicks'] as $path => $key) {
            for ($offset = 0; $offset + self::PAGE <= 10000; $offset += self::PAGE) {
                $response = Http::withHeaders(['X-Postmark-Server-Token' => $token])
                    ->acceptJson()
                    ->get("https://api.postmarkapp.com/messages/outbound/{$path}", [
                        'count' => self::PAGE,
                        'offset' => $offset,
                    ]);

                if ($response->failed()) {
                    $this->error("Postmark {$path}: HTTP {$response->status()} " . $response->json('Message'));

                    return self::FAILURE;
                }

                $events = $response->json($key) ?? [];
                $reachedCutoff = false;

                foreach ($events as $event) {
                    if (Carbon::parse($event['ReceivedAt'])->lt($since)) {
                        $reachedCutoff = true;

                        continue;
                    }

                    // The event listings leave Metadata empty; the message itself has it.
                    if (empty($event['Metadata']) && filled($event['MessageID'] ?? null)) {
                        $event['Metadata'] = $this->metadataFor($token, $event['MessageID']);
                    }

                    $status = $this->ours($event)
                        ? ($recorder->record($event)['status'] ?? 'unknown')
                        : 'not ours';

                    $totals[$path][$status] = ($totals[$path][$status] ?? 0) + 1;
                }

                // Newest first, so once a page reaches the cutoff there is nothing further.
                if ($reachedCutoff || count($events) < self::PAGE) {
                    break;
                }
            }
        }

        foreach ($totals as $path => $counts) {
            $this->line(ucfirst($path) . ': ' . collect($counts)->map(fn ($n, $s) => "{$n} {$s}")->implode(', '));
        }

        if ($totals === []) {
            $this->line('No opens or clicks in that window.');
        }

        return self::SUCCESS;
    }

    /** @var array<string, array<string, string>> */
    private array $metadataCache = [];

    /** @return array<string, string> */
    private function metadataFor(string $token, string $messageId): array
    {
        return $this->metadataCache[$messageId] ??= (array) (Http::withHeaders(['X-Postmark-Server-Token' => $token])
            ->acceptJson()
            ->get("https://api.postmarkapp.com/messages/outbound/{$messageId}/details")
            ->json('Metadata') ?? []);
    }

    /** Whether this database sent the email the event belongs to. */
    private function ours(array $event): bool
    {
        $metadata = array_change_key_case($event['Metadata'] ?? [], CASE_LOWER);
        $prospectId = $metadata['prospect-id'] ?? null;
        $recipient = $event['Recipient'] ?? null;

        if (! $prospectId || ! ctype_digit((string) $prospectId) || blank($recipient)) {
            return false;
        }

        return ProspectActivity::where('prospect_id', (int) $prospectId)
            ->where('type', ProspectActivity::EMAIL_SENT)
            ->where('meta->email', $recipient)
            ->exists();
    }
}
