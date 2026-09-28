<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignStep;
use Illuminate\Console\Command;

/**
 * Creates the promotional-products preview campaign and its four steps.
 *
 * Separate from the template seeder because the two answer different questions: that one
 * owns the words, this one owns the order and the timing. Re-running it leaves an existing
 * campaign's name and description alone — somebody may have renamed it — but does put the
 * steps back the way they shipped, which is the useful half of a reinstall.
 */
class InstallPreviewCampaign extends Command
{
    protected $signature = 'campaigns:install-preview {--force : Reset the steps of an existing campaign}';

    protected $description = 'Create the promotional products preview drip campaign';

    /** @var array<int, array{name: string, template_key: string, delay_days: int}> */
    protected array $steps = [
        ['name' => 'The concept', 'template_key' => 'promo_preview_intro', 'delay_days' => 0],
        ['name' => 'Nudge', 'template_key' => 'promo_preview_nudge', 'delay_days' => 3],
        ['name' => 'What launching looks like', 'template_key' => 'promo_preview_details', 'delay_days' => 4],
        ['name' => 'Last note', 'template_key' => 'promo_preview_last', 'delay_days' => 7],
    ];

    public function handle(): int
    {
        $campaign = Campaign::firstOrCreate(
            ['key' => 'promo-preview'],
            [
                'name' => 'Promo shops · preview',
                'description' => 'Cold outreach to promotional products companies with a website '
                    . 'concept already built for them. Each prospect needs a preview URL before '
                    . 'they can be enrolled.',
                'is_active' => true,
                'stop_on_reply' => true,
                'stop_on_booking' => true,
            ],
        );

        $existingSteps = $campaign->steps()->count();

        if ($existingSteps > 0 && ! $this->option('force')) {
            $this->line("Campaign already installed with {$existingSteps} step(s) — use --force to reset them.");

            return self::SUCCESS;
        }

        $campaign->steps()->delete();

        foreach ($this->steps as $index => $step) {
            CampaignStep::create([
                'campaign_id' => $campaign->id,
                'position' => $index + 1,
                'name' => $step['name'],
                'template_key' => $step['template_key'],
                // Days after the PREVIOUS step, so the sequence lands on days 0, 3, 7 and 14.
                'delay_days' => $step['delay_days'],
                'is_active' => true,
            ]);
        }

        $this->info('Installed "' . $campaign->name . '" with ' . count($this->steps) . ' steps.');
        $this->line('Seed the copy with: php artisan email:seed-templates');

        return self::SUCCESS;
    }
}
