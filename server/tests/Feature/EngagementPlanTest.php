<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\EngagementPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementPlanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetInstance();
    }

    public function test_unknown_units_fall_back_to_the_default(): void
    {
        $this->assertSame('sprint', EngagementPlan::normaliseUnit(null));
        $this->assertSame('sprint', EngagementPlan::normaliseUnit('weeks'));
        $this->assertSame('day', EngagementPlan::normaliseUnit('Days'));
        $this->assertSame('hour', EngagementPlan::normaliseUnit(' HOURS '));
    }

    public function test_quantity_is_clamped_to_the_units_range(): void
    {
        $this->assertSame(24, EngagementPlan::make('sprint', 99)->quantity);
        $this->assertSame(120, EngagementPlan::make('day', 500)->quantity);
        $this->assertSame(1, EngagementPlan::make('hour', -5)->quantity);

        // A missing quantity falls back to the unit's default.
        $this->assertSame(4, EngagementPlan::make('sprint', null)->quantity);
        $this->assertSame(10, EngagementPlan::make('day', null)->quantity);
    }

    public function test_phase_counts_fold_quantity_into_natural_blocks(): void
    {
        // Sprints are one phase each.
        $this->assertSame(1, EngagementPlan::make('sprint', 1)->phaseCount);
        $this->assertSame(6, EngagementPlan::make('sprint', 6)->phaseCount);

        // Days fold into 5-day phases, capped at 8.
        $this->assertSame(1, EngagementPlan::make('day', 3)->phaseCount);
        $this->assertSame(2, EngagementPlan::make('day', 10)->phaseCount);
        $this->assertSame(8, EngagementPlan::make('day', 120)->phaseCount);

        // Hours fold into 40-hour phases, capped at 8.
        $this->assertSame(1, EngagementPlan::make('hour', 12)->phaseCount);
        $this->assertSame(5, EngagementPlan::make('hour', 200)->phaseCount);
        $this->assertSame(8, EngagementPlan::make('hour', 2000)->phaseCount);
    }

    public function test_allocation_always_sums_to_the_purchased_quantity(): void
    {
        $plan = EngagementPlan::make('day', 12); // 3 phases

        $this->assertSame(12, array_sum($plan->allocate([6, 4, 2])));
        $this->assertSame(12, array_sum($plan->allocate([1, 1, 1])));      // even-ish split
        $this->assertSame(12, array_sum($plan->allocate([]))); // nothing usable
        $this->assertSame(12, array_sum($plan->allocate([0, 0, 0])));
        $this->assertSame(12, array_sum($plan->allocate([100, 1, 1])));    // lopsided
        $this->assertSame(12, array_sum($plan->allocate([5, 5, 5, 5, 5]))); // too many weights
        $this->assertSame(12, array_sum($plan->allocate([9])));            // too few weights
    }

    public function test_allocation_respects_the_proposed_weighting(): void
    {
        $plan = EngagementPlan::make('hour', 100); // 3 phases

        $this->assertSame([50, 30, 20], $plan->allocate([50, 30, 20]));
        $this->assertSame([50, 30, 20], $plan->allocate([5, 3, 2])); // scaled proportionally
    }

    public function test_every_phase_gets_at_least_one_unit(): void
    {
        $plan = EngagementPlan::make('day', 12);

        foreach ($plan->allocate([100, 0, 0]) as $quantity) {
            $this->assertGreaterThanOrEqual(1, $quantity);
        }
    }

    public function test_totals_and_labels_read_correctly(): void
    {
        $sprints = EngagementPlan::make('sprint', 4);
        $this->assertEqualsWithDelta(12000, $sprints->total(), 0.01);
        $this->assertSame('sprints', $sprints->unitLabel());
        $this->assertSame('sprint', $sprints->unitLabel(1));
        $this->assertSame('Sprint 2', $sprints->phaseLabel(2));
        $this->assertSame('4 sprints at $3,000 each', $sprints->quantityLabel());

        $days = EngagementPlan::make('day', 1);
        $this->assertSame('day', $days->unitLabel());
        $this->assertSame('Phase 1', $days->phaseLabel(1));
        $this->assertSame('1 day at $1,000 each', $days->quantityLabel());
    }

    public function test_rate_tracks_the_settings_row(): void
    {
        $this->assertEqualsWithDelta(175, EngagementPlan::make('hour', 1)->rate, 0.01);

        Setting::instance()->update(['hourly_rate' => 225]);
        Setting::forgetInstance();

        $this->assertEqualsWithDelta(225, EngagementPlan::make('hour', 1)->rate, 0.01);
    }
}
