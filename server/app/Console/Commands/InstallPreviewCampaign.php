<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignStep;
use Illuminate\Console\Command;

/**
 * Creates a preview drip campaign and its four steps.
 *
 * Two variants share one shape — concept, nudge, what launching looks like, last note — and
 * differ only in copy: "promo" pitches promotional products companies, "general" pitches any
 * business on our track record and the speed AI now gives us.
 *
 * Separate from the template seeder because the two answer different questions: that one
 * owns the words, this one owns the order and the timing. Re-running it leaves an existing
 * campaign's name and description alone — somebody may have renamed it — but does put the
 * steps back the way they shipped, which is the useful half of a reinstall.
 */
class InstallPreviewCampaign extends Command
{
    protected $signature = 'campaigns:install-preview
        {variant=promo : Which campaign: promo or general}
        {--force : Reset the steps of an existing campaign}';

    protected $description = 'Create a preview drip campaign (promo shops or general website)';

    /**
     * Days are after the PREVIOUS step, so each sequence lands on days 0, 3, 7 and 14.
     *
     * @return array<string, array{key: string, name: string, description: string, prefix: string}>
     */
    protected function variants(): array
    {
        return [
            'promo' => [
                'key' => 'promo-preview',
                'name' => 'Promo shops · preview',
                'description' => 'Cold outreach to promotional products companies with a website '
                    . 'concept already built for them. Each prospect needs a preview URL before '
                    . 'they can be enrolled.',
                'prefix' => 'promo_preview',
            ],
            'general' => [
                'key' => 'general-preview',
                'name' => 'General website · preview',
                'description' => 'Cold outreach to any business with a website concept already '
                    . 'built for them — no industry framing. Each prospect needs a preview URL '
                    . 'before they can be enrolled.',
                'prefix' => 'general_preview',
            ],
        ];
    }

    /** @var array<int, array{name: string, suffix: string, delay_days: int}> */
    protected array $steps = [
        ['name' => 'The concept', 'suffix' => 'intro', 'delay_days' => 0],
        ['name' => 'Nudge', 'suffix' => 'nudge', 'delay_days' => 3],
        ['name' => 'What launching looks like', 'suffix' => 'details', 'delay_days' => 4],
        ['name' => 'Last note', 'suffix' => 'last', 'delay_days' => 7],
    ];

    public function handle(): int
    {
        $variant = $this->variants()[$this->argument('variant')] ?? null;

        if (! $variant) {
            $this->error('Unknown variant "' . $this->argument('variant') . '" — use promo or general.');

            return self::FAILURE;
        }

        $campaign = Campaign::firstOrCreate(
            ['key' => $variant['key']],
            [
                'name' => $variant['name'],
                'description' => $variant['description'],
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
                'template_key' => $variant['prefix'] . '_' . $step['suffix'],
                'delay_days' => $step['delay_days'],
                'is_active' => true,
            ]);
        }

        $this->info('Installed "' . $campaign->name . '" with ' . count($this->steps) . ' steps.');
        $this->line('Seed the copy with: php artisan email:seed-templates');

        return self::SUCCESS;
    }
}
