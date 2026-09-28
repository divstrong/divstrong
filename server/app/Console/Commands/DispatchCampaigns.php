<?php

namespace App\Console\Commands;

use App\Support\CampaignRunner;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Sends whatever the drip campaigns owe today.
 *
 * Run every fifteen minutes by the scheduler; the sending window and the per-run cap are
 * what keep a batch of two hundred enrolments from leaving as two hundred messages in one
 * minute, which is the shape of a spam run whatever the content says.
 */
class DispatchCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch
        {--limit=25 : Maximum emails to send this run}
        {--force : Send even outside the configured sending window}
        {--dry : Report what is due without sending}';

    protected $description = 'Send any campaign steps that are due';

    public function handle(): int
    {
        if (! $this->option('force') && ! $this->withinSendingWindow()) {
            $this->line('Outside the sending window — nothing sent.');

            return self::SUCCESS;
        }

        if ($this->option('dry')) {
            $due = \App\Models\CampaignEnrollment::due()->with('prospect', 'campaign')->get();

            $this->info($due->count() . ' enrolment(s) due:');

            foreach ($due as $enrollment) {
                $this->line(sprintf(
                    '  %s — %s (step after %s)',
                    $enrollment->prospect?->company ?: $enrollment->prospect?->email ?: 'unknown',
                    $enrollment->campaign?->name,
                    $enrollment->last_step_position ?? 'start',
                ));
            }

            return self::SUCCESS;
        }

        $counts = CampaignRunner::dispatchDue((int) $this->option('limit'));

        $this->info(sprintf(
            'Campaigns: %d sent, %d stopped or completed, %d skipped.',
            $counts['sent'],
            $counts['stopped'],
            $counts['skipped'],
        ));

        return self::SUCCESS;
    }

    /**
     * Business hours in the host's timezone, weekdays only.
     *
     * Cold email that arrives at 3am local reads as automated before it is read at all,
     * and a send window is the cheapest deliverability control there is.
     */
    protected function withinSendingWindow(): bool
    {
        $now = Carbon::now(config('scheduling.timezone'));

        if ($now->isWeekend()) {
            return false;
        }

        return $now->hour >= (int) config('prospecting.outreach.send_from_hour', 8)
            && $now->hour < (int) config('prospecting.outreach.send_until_hour', 17);
    }
}
