<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\Client;
use App\Models\Setting;
use App\Models\TermsLibrary;
use App\Models\User;
use App\Services\ClaudeService;
use App\Services\ClientProposalBuilder;
use App\Support\EngagementMix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientProposalBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetInstance();
        Setting::instance()->update(['sprint_rate' => 3000, 'daily_rate' => 1250, 'hourly_rate' => 175]);
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

            public function generateClientProposalContent(array $brief, EngagementMix $mix): array
            {
                $this->calledWith = compact('brief', 'mix');

                return $this->content;
            }
        };
    }

    private function client(): Client
    {
        return Client::create([
            'name' => 'Dana Reyes',
            'email' => 'dana@northsidehvac.test',
            'company' => 'Northside HVAC',
            'domain' => 'northsidehvac.test',
        ]);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret'),
        ]);
    }

    private function content(int $sprints, array $extra = []): array
    {
        $list = [];

        for ($number = 1; $number <= $sprints; $number++) {
            $list[] = [
                'number' => $number,
                'title' => "Theme {$number}",
                'goal' => "You will be able to do thing {$number}.",
                'scope_items' => [
                    ['title' => "Item {$number}a", 'description' => 'desc', 'bullets' => ['b1', 'b2']],
                    ['title' => "Item {$number}b", 'description' => 'desc', 'bullets' => []],
                ],
            ];
        }

        return array_merge([
            'project_title' => 'Northside HVAC Website Rebuild',
            'introduction' => '<p>Intro.</p>',
            'cost_notes' => 'Fixed-price sprints.',
            'sprints' => $list,
            'day_work' => null,
            'hour_work' => null,
        ], $extra);
    }

    public function test_a_sprint_only_engagement_bills_and_schedules_one_row_per_sprint(): void
    {
        TermsLibrary::create(['content' => 'Payment is due in 30 days.', 'is_active' => true, 'sort_order' => 0]);
        TermsLibrary::create(['content' => 'Inactive term.', 'is_active' => false, 'sort_order' => 1]);

        $client = $this->client();
        $user = $this->user();
        $claude = $this->stubClaude($this->content(3));

        $proposal = (new ClientProposalBuilder($claude))
            ->build($client, EngagementMix::make(3), null, $user->id);

        $this->assertSame(ProposalStatus::Draft, $proposal->status);
        $this->assertSame($client->id, $proposal->client_id);
        $this->assertSame('Northside HVAC Website Rebuild', $proposal->project_title);
        $this->assertSame('Dana Reyes', $proposal->client_name);
        $this->assertSame('dana@northsidehvac.test', $proposal->client_email);
        $this->assertSame('Northside HVAC', $proposal->client_company);
        $this->assertSame('northsidehvac.test', $proposal->client_domain);
        $this->assertSame('<p>Intro.</p>', $proposal->introduction);
        $this->assertSame('Fixed-price sprints.', $proposal->cost_notes);
        $this->assertTrue((bool) $proposal->investment_enabled);
        $this->assertTrue((bool) $proposal->milestones_enabled);

        // Scope: 2 items per sprint, categorised by sprint, in delivery order.
        $this->assertCount(6, $proposal->scopeItems);
        $this->assertSame(
            ['Sprint #1 — Theme 1', 'Sprint #2 — Theme 2', 'Sprint #3 — Theme 3'],
            $proposal->scopeItems->pluck('category')->unique()->values()->all(),
        );
        $this->assertSame([0, 1, 2, 3, 4, 5], $proposal->scopeItems->pluck('sort_order')->all());
        $this->assertSame(['b1', 'b2'], $proposal->scopeItems->first()->bullets);

        // Investment: exactly one row per sprint at the sprint rate.
        $this->assertSame(
            ['Sprint #1 — Theme 1', 'Sprint #2 — Theme 2', 'Sprint #3 — Theme 3'],
            $proposal->costItems->pluck('description')->all(),
        );
        foreach ($proposal->costItems as $item) {
            $this->assertSame(1, $item->quantity);
            $this->assertEquals(3000, $item->unit_price);
        }
        $this->assertEqualsWithDelta(9000, $proposal->total, 0.01);

        // Milestones: one per sprint, carrying the plain-language goal.
        $this->assertCount(3, $proposal->milestones);
        $this->assertSame('due upon completion of Sprint #1 — Theme 1', $proposal->milestones->first()->title);
        $this->assertSame('You will be able to do thing 1.', $proposal->milestones->first()->description);
        $this->assertSame('Sprint #1', $proposal->milestones->first()->due_description);
        $this->assertEqualsWithDelta(100, $proposal->milestones->sum(fn ($m) => (float) $m->percentage), 0.001);
        $this->assertEqualsWithDelta(9000, $proposal->milestones->sum(fn ($m) => (float) $m->amount), 0.01);

        // Only active terms are copied.
        $this->assertCount(1, $proposal->terms);

        // The drafter is told who the client is and how the work was sized.
        $this->assertSame('Northside HVAC', $claude->calledWith['brief']['client_company']);
        $this->assertSame(3, $claude->calledWith['mix']->sprints);
    }

    public function test_day_and_hour_time_become_their_own_investment_rows(): void
    {
        $client = $this->client();
        $user = $this->user();

        $proposal = (new ClientProposalBuilder($this->stubClaude($this->content(2, [
            'day_work' => 'Content migration and design refinements',
            'hour_work' => 'Ad-hoc fixes and small enhancements',
        ]))))->build($client, EngagementMix::make(2, 10, 20), null, $user->id);

        $this->assertSame([
            'Sprint #1 — Theme 1',
            'Sprint #2 — Theme 2',
            'Development — Content migration and design refinements',
            'Development — Ad-hoc fixes and small enhancements',
        ], $proposal->costItems->pluck('description')->all());

        $days = $proposal->costItems[2];
        $this->assertSame(10, $days->quantity);
        $this->assertEquals(1250, $days->unit_price);
        $this->assertEquals(12500, $days->amount);

        $hours = $proposal->costItems[3];
        $this->assertSame(20, $hours->quantity);
        $this->assertEquals(175, $hours->unit_price);
        $this->assertEquals(3500, $hours->amount);

        $this->assertEqualsWithDelta(22000, $proposal->total, 0.01);

        // The supplemental time gets its own closing milestone so the
        // percentages still describe the whole engagement.
        $this->assertCount(3, $proposal->milestones);
        $this->assertSame(
            'due as additional development time is used',
            $proposal->milestones->last()->title,
        );
        $this->assertSame(
            '10 days at $1,250 each, 20 hours at $175 each',
            $proposal->milestones->last()->description,
        );
        $this->assertEqualsWithDelta(100, $proposal->milestones->sum(fn ($m) => (float) $m->percentage), 0.001);
        $this->assertEqualsWithDelta(22000, $proposal->milestones->sum(fn ($m) => (float) $m->amount), 0.01);
    }

    public function test_milestone_percentages_always_settle_to_one_hundred(): void
    {
        $client = $this->client();
        $user = $this->user();

        // 7 equal sprints divide into 14.2857…% each, which cannot be
        // represented exactly at two decimals.
        $proposal = (new ClientProposalBuilder($this->stubClaude($this->content(7))))
            ->build($client, EngagementMix::make(7), null, $user->id);

        $this->assertCount(7, $proposal->milestones);
        $this->assertEqualsWithDelta(100, $proposal->milestones->sum(fn ($m) => (float) $m->percentage), 0.001);
    }

    public function test_the_proposal_is_valid_for_six_weeks(): void
    {
        $client = $this->client();
        $user = $this->user();

        $proposal = (new ClientProposalBuilder($this->stubClaude($this->content(1))))
            ->build($client, EngagementMix::make(1), null, $user->id);

        $this->assertSame(
            now()->addWeeks(6)->toDateString(),
            $proposal->valid_until->toDateString(),
        );
    }

    public function test_a_typed_project_title_wins_over_the_drafted_one(): void
    {
        $client = $this->client();
        $user = $this->user();
        $claude = $this->stubClaude($this->content(1));

        $proposal = (new ClientProposalBuilder($claude))
            ->build($client, EngagementMix::make(1), 'Spring Booking Push', $user->id);

        $this->assertSame('Spring Booking Push', $proposal->project_title);
        $this->assertSame('Spring Booking Push', $claude->calledWith['brief']['project_title']);
    }

    public function test_missing_drafted_content_still_produces_a_usable_proposal(): void
    {
        $client = $this->client();
        $user = $this->user();

        $proposal = (new ClientProposalBuilder($this->stubClaude([
            'project_title' => null,
            'introduction' => '',
            'cost_notes' => null,
            'sprints' => [['number' => 1, 'title' => '', 'goal' => '', 'scope_items' => []]],
        ])))->build($client, EngagementMix::make(1, 0, 0), null, $user->id);

        $this->assertSame('Northside HVAC Project', $proposal->project_title);
        $this->assertStringContainsString('1 sprint at $3,000 each', $proposal->cost_notes);
        $this->assertSame('Sprint #1 — Delivery', $proposal->costItems->first()->description);
        $this->assertEqualsWithDelta(100, (float) $proposal->milestones->first()->percentage, 0.001);
    }
}
