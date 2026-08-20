<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\RfpScreen;
use App\Models\Setting;
use App\Models\TermsLibrary;
use App\Models\User;
use App\Services\ClaudeService;
use App\Services\RfpProposalBuilder;
use App\Support\EngagementPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfpProposalBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetInstance();
    }

    /** A ClaudeService that returns canned content instead of calling the API. */
    private function stubClaude(array $content): ClaudeService
    {
        return new class($content) extends ClaudeService
        {
            public array $calledWith = [];

            public function __construct(private array $content)
            {
                parent::__construct();
            }

            public function generateProposalContent(array $rfp, EngagementPlan $plan): array
            {
                $this->calledWith = compact('rfp', 'plan');

                return $this->content;
            }
        };
    }

    private function screen(): RfpScreen
    {
        $user = User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret'),
        ]);

        return RfpScreen::create([
            'user_id' => $user->id,
            'filename' => 'rfp.pdf',
            'original_filename' => 'rfp.pdf',
            'file_path' => 'rfp-documents/rfp.pdf',
            'file_type' => 'pdf',
            'rfp_name' => 'City Website Redesign',
            'contact_name' => 'Pat Reed',
            'contact_email' => 'pat@city.gov',
            'contact_department' => 'City of Springfield',
            'summary' => 'Rebuild the municipal website.',
            'requirements' => ['WCAG 2.1 AA', 'CMS training'],
            'red_flags' => ['Tight deadline'],
            'submission_requirements' => ['Three bound copies'],
            'locality_city' => 'Springfield',
            'locality_state' => 'IL',
            'score' => 82,
            'status' => 'completed',
            'prompt' => 'test prompt',
        ]);
    }

    /** @param  array<int, int>  $quantities  one per phase */
    private function content(array $quantities): array
    {
        $phases = [];

        foreach ($quantities as $i => $quantity) {
            $number = $i + 1;
            $phases[] = [
                'number' => $number,
                'title' => "Theme {$number}",
                'summary' => "Delivers phase {$number}.",
                'quantity' => $quantity,
                'scope_items' => [
                    ['title' => "Item {$number}a", 'description' => 'desc', 'bullets' => ['b1', 'b2']],
                    ['title' => "Item {$number}b", 'description' => 'desc', 'bullets' => []],
                ],
            ];
        }

        return [
            'introduction' => '<p>Intro.</p>',
            'cost_notes' => 'Fixed-price phases.',
            'phases' => $phases,
            'contact_name' => 'Pat Reed',
            'contact_email' => 'pat@city.gov',
            'contact_company' => 'City of Springfield',
        ];
    }

    public function test_a_sprint_engagement_bills_one_row_per_sprint(): void
    {
        TermsLibrary::create(['content' => 'Payment is due in 30 days.', 'is_active' => true, 'sort_order' => 0]);
        TermsLibrary::create(['content' => 'Inactive term.', 'is_active' => false, 'sort_order' => 1]);

        $screen = $this->screen();
        $claude = $this->stubClaude($this->content([1, 1, 1]));
        $plan = EngagementPlan::make('sprint', 3);

        $proposal = (new RfpProposalBuilder($claude))->build($screen, $plan, $screen->user_id);

        $this->assertSame('City Website Redesign', $proposal->project_title);
        $this->assertSame(ProposalStatus::Draft, $proposal->status);
        $this->assertSame('<p>Intro.</p>', $proposal->introduction);
        $this->assertSame('Fixed-price phases.', $proposal->cost_notes);
        $this->assertTrue((bool) $proposal->investment_enabled);
        $this->assertSame('City of Springfield', $proposal->client_company);

        // Scope: 2 items per phase, categorised by sprint, in delivery order.
        $this->assertCount(6, $proposal->scopeItems);
        $this->assertSame(
            ['Sprint 1 — Theme 1', 'Sprint 2 — Theme 2', 'Sprint 3 — Theme 3'],
            $proposal->scopeItems->pluck('category')->unique()->values()->all(),
        );
        $this->assertSame([0, 1, 2, 3, 4, 5], $proposal->scopeItems->pluck('sort_order')->all());
        $this->assertSame(['b1', 'b2'], $proposal->scopeItems->first()->bullets);

        // Investment: exactly one row per sprint at the sprint rate.
        $this->assertCount(3, $proposal->costItems);
        $this->assertSame(
            'Sprint 1 — Theme 1: Delivers phase 1.',
            $proposal->costItems->first()->description,
        );
        foreach ($proposal->costItems as $item) {
            $this->assertSame(1, $item->quantity);
            $this->assertEquals(3000, $item->unit_price);
            $this->assertEquals(3000, $item->amount);
        }
        $this->assertEqualsWithDelta(9000, $proposal->total, 0.01);

        // Only active terms are copied, and the screen is linked to the proposal.
        $this->assertCount(1, $proposal->terms);
        $this->assertSame($proposal->id, $screen->fresh()->proposal_id);

        // The drafter receives the analysis, the source document, and the plan.
        $this->assertSame('sprint', $claude->calledWith['plan']->unit);
        $this->assertSame(3, $claude->calledWith['plan']->quantity);
        $this->assertSame('rfp-documents/rfp.pdf', $claude->calledWith['rfp']['file_path']);
        $this->assertSame('Springfield, IL', $claude->calledWith['rfp']['locality']);
        $this->assertSame(['WCAG 2.1 AA', 'CMS training'], $claude->calledWith['rfp']['requirements']);
    }

    public function test_a_day_engagement_splits_days_across_phases_and_totals_exactly(): void
    {
        $screen = $this->screen();
        $plan = EngagementPlan::make('day', 12);

        // 12 days folds into 3 phases of 5, 5, 2.
        $this->assertSame(3, $plan->phaseCount);
        $this->assertEqualsWithDelta(12000, $plan->total(), 0.01);

        $proposal = (new RfpProposalBuilder($this->stubClaude($this->content([6, 4, 2]))))
            ->build($screen, $plan, $screen->user_id);

        $this->assertCount(3, $proposal->costItems);
        $this->assertSame([6, 4, 2], $proposal->costItems->pluck('quantity')->all());
        $this->assertSame(12, $proposal->costItems->sum('quantity'));
        $this->assertEqualsWithDelta(12000, $proposal->total, 0.01);

        // Non-sprint units label their groups "Phase N".
        $this->assertSame(
            ['Phase 1 — Theme 1', 'Phase 2 — Theme 2', 'Phase 3 — Theme 3'],
            $proposal->scopeItems->pluck('category')->unique()->values()->all(),
        );
        foreach ($proposal->costItems as $item) {
            $this->assertEquals(1000, $item->unit_price);
            $this->assertEquals($item->quantity * 1000, $item->amount);
        }
    }

    public function test_an_hour_engagement_uses_the_hourly_rate(): void
    {
        $screen = $this->screen();
        $plan = EngagementPlan::make('hour', 120);

        $this->assertSame(3, $plan->phaseCount);

        $proposal = (new RfpProposalBuilder($this->stubClaude($this->content([50, 40, 30]))))
            ->build($screen, $plan, $screen->user_id);

        $this->assertSame(120, $proposal->costItems->sum('quantity'));
        $this->assertEqualsWithDelta(120 * 175, $proposal->total, 0.01);
    }

    public function test_rates_come_from_settings(): void
    {
        Setting::instance()->update(['sprint_rate' => 4200]);
        Setting::forgetInstance();

        $screen = $this->screen();
        $plan = EngagementPlan::make('sprint', 2);

        $proposal = (new RfpProposalBuilder($this->stubClaude($this->content([1, 1]))))
            ->build($screen, $plan, $screen->user_id);

        $this->assertEqualsWithDelta(8400, $proposal->total, 0.01);
    }

    public function test_the_scope_prompt_is_passed_through_to_the_drafter(): void
    {
        $screen = $this->screen();
        $claude = $this->stubClaude($this->content([1]));

        (new RfpProposalBuilder($claude))->build(
            $screen,
            EngagementPlan::make('sprint', 1, '  Lead with accessibility remediation.  '),
            $screen->user_id,
        );

        $this->assertSame('Lead with accessibility remediation.', $claude->calledWith['plan']->scopePrompt);
    }

    public function test_a_blank_scope_prompt_is_treated_as_absent(): void
    {
        $this->assertNull(EngagementPlan::make('sprint', 1, '   ')->scopePrompt);
        $this->assertNull(EngagementPlan::make('sprint', 1, null)->scopePrompt);
    }
}
