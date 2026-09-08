<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\EngagementMix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementMixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetInstance();
    }

    public function test_quantities_are_clamped_to_each_units_range(): void
    {
        $mix = EngagementMix::make(99, 500, 9999);

        $this->assertSame(24, $mix->sprints);
        $this->assertSame(120, $mix->days);
        $this->assertSame(2000, $mix->hours);
    }

    public function test_an_engagement_always_holds_at_least_one_sprint(): void
    {
        // Scope, investment rows and milestones are all built per sprint, so a
        // zero-sprint mix would produce an empty proposal.
        $this->assertSame(1, EngagementMix::make(0)->sprints);
        $this->assertSame(1, EngagementMix::make(null)->sprints);
        $this->assertSame(1, EngagementMix::make(-3)->sprints);
    }

    public function test_day_and_hour_time_is_optional(): void
    {
        $mix = EngagementMix::make(4);

        $this->assertSame(0, $mix->days);
        $this->assertSame(0, $mix->hours);
        $this->assertFalse($mix->hasDays());
        $this->assertFalse($mix->hasHours());
        $this->assertFalse($mix->hasSupplemental());
        $this->assertNull($mix->dayLine());
        $this->assertNull($mix->hourLine());
    }

    public function test_subtotals_and_total_use_the_settings_rates(): void
    {
        Setting::instance()->update(['sprint_rate' => 3000, 'daily_rate' => 1250, 'hourly_rate' => 175]);
        Setting::forgetInstance();

        $mix = EngagementMix::make(4, 10, 20);

        $this->assertEqualsWithDelta(12000, $mix->sprintSubtotal(), 0.01);
        $this->assertEqualsWithDelta(12500, $mix->daySubtotal(), 0.01);
        $this->assertEqualsWithDelta(3500, $mix->hourSubtotal(), 0.01);
        $this->assertEqualsWithDelta(28000, $mix->total(), 0.01);
    }

    public function test_rates_can_be_overridden_without_touching_settings(): void
    {
        $mix = EngagementMix::make(2, 0, 0, null, ['sprint' => 4200.0, 'day' => 1000.0, 'hour' => 150.0]);

        $this->assertEqualsWithDelta(8400, $mix->total(), 0.01);
    }

    public function test_summary_line_names_only_what_was_bought(): void
    {
        $rates = ['sprint' => 3000.0, 'day' => 1250.0, 'hour' => 175.0];

        $this->assertSame(
            '4 sprints at $3,000 each, 10 days at $1,250 each, 20 hours at $175 each — $28,000 total',
            EngagementMix::make(4, 10, 20, null, $rates)->summaryLine(),
        );

        $this->assertSame(
            '1 sprint at $3,000 each — $3,000 total',
            EngagementMix::make(1, 0, 0, null, $rates)->summaryLine(),
        );
    }

    public function test_a_blank_scope_prompt_is_treated_as_absent(): void
    {
        $this->assertSame('Rebuild the site.', EngagementMix::make(1, 0, 0, '  Rebuild the site.  ')->scopePrompt);
        $this->assertNull(EngagementMix::make(1, 0, 0, '   ')->scopePrompt);
        $this->assertNull(EngagementMix::make(1, 0, 0, null)->scopePrompt);
    }

    public function test_sprint_labels_are_numbered(): void
    {
        $this->assertSame('Sprint #3', EngagementMix::make(4)->sprintLabel(3));
    }
}
