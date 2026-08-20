<?php

namespace Tests\Feature;

use App\Filament\Pages\Settings;
use App\Filament\Resources\RfpScreenResource\Pages\ViewRfpScreen;
use App\Models\RfpScreen;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Smoke tests for the admin surfaces the sprint/day/hour engagement touches:
 * the Rates tab and the Generate Proposal modal.
 */
class ProposalGenerationScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetInstance();

        // No role assigned = full access.
        $this->actingAs(User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]));
    }

    public function test_the_rates_tab_saves_and_drives_the_public_pricing_section(): void
    {
        Livewire::test(Settings::class)
            ->assertFormSet([
                'hourly_rate' => '175.00',
                'daily_rate' => '1000.00',
                'sprint_rate' => '3000.00',
                'hours_per_day' => 10,
            ])
            ->fillForm([
                'hourly_rate' => 195,
                'daily_rate' => 1250,
                'sprint_rate' => 3500,
                'hours_per_day' => 8,
            ])
            ->call('saveRates')
            ->assertHasNoFormErrors();

        Setting::forgetInstance();

        $this->assertSame(
            ['hour' => 195.0, 'day' => 1250.0, 'sprint' => 3500.0, 'hours_per_day' => 8],
            Setting::rates(),
        );

        $this->get('/')
            ->assertOk()
            ->assertSee('$195')
            ->assertSee('$1,250')
            ->assertSee('$3,500')
            ->assertSee('dedicated 8-hour day');
    }

    public function test_the_generate_proposal_modal_renders_its_engagement_fields(): void
    {
        $screen = RfpScreen::create([
            'user_id' => auth()->id(),
            'filename' => 'rfp.pdf',
            'original_filename' => 'rfp.pdf',
            'file_path' => 'rfp-documents/rfp.pdf',
            'file_type' => 'pdf',
            'rfp_name' => 'City Website Redesign',
            'summary' => 'Rebuild the municipal website.',
            'requirements' => [],
            'red_flags' => [],
            'submission_requirements' => [],
            'prompt' => 'test prompt',
            'score' => 82,
            'status' => 'completed',
        ]);

        Livewire::test(ViewRfpScreen::class, ['record' => $screen->id])
            ->assertOk()
            ->mountAction('createProposal')
            ->assertSchemaStateSet([
                'unit' => 'sprint',
                'quantity' => 4,
            ])
            // Switching the unit re-defaults the quantity for that unit.
            ->set('mountedActions.0.data.unit', 'day')
            ->assertSchemaStateSet([
                'unit' => 'day',
                'quantity' => 10,
            ]);
    }

    public function test_the_modal_shows_a_live_total_and_phase_split(): void
    {
        // Filament renders modal bodies outside the component HTML, so exercise
        // the placeholder's content function directly.
        $summary = new \ReflectionMethod(ViewRfpScreen::class, 'engagementSummary');
        $summary->setAccessible(true);
        $page = new ViewRfpScreen();

        $sprints = (string) $summary->invoke($page, 'sprint', 4);
        $this->assertStringContainsString('$12,000', $sprints);
        $this->assertStringContainsString('4 sprints × $3,000', $sprints);
        $this->assertStringContainsString('4 sprints — one investment row each', $sprints);

        $days = (string) $summary->invoke($page, 'day', 10);
        $this->assertStringContainsString('$10,000', $days);
        $this->assertStringContainsString('10 days × $1,000', $days);
        $this->assertStringContainsString('2 phases — one investment row each', $days);

        $hours = (string) $summary->invoke($page, 'hour', 120);
        $this->assertStringContainsString('$21,000', $hours);
        $this->assertStringContainsString('3 phases', $hours);
    }
}
