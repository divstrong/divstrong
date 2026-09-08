<?php

namespace Tests\Feature;

use App\Filament\Resources\ProposalResource\Pages\ListProposals;
use App\Models\Client;
use App\Models\Setting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The modal form is only built when the action is mounted, so these cover the
 * parts a syntax check cannot: that the schema resolves, that the client search
 * finds people by company, and that the live investment total adds up.
 */
class ListProposalsGenerateActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetInstance();
        Setting::instance()->update(['sprint_rate' => 3000, 'daily_rate' => 1250, 'hourly_rate' => 175]);
        Setting::forgetInstance();

        Filament::setCurrentPanel('admin');

        $this->actingAs($this->admin());
    }

    /** A user with no role has full access — see User::hasPermission(). */
    private function admin(): User
    {
        return User::create([
            'name' => 'Tester',
            'email' => 'tester@example.com',
            'password' => bcrypt('secret'),
        ]);
    }

    public function test_the_generate_action_mounts_with_its_defaults(): void
    {
        Livewire::test(ListProposals::class)
            ->mountAction('generateProposal')
            ->assertActionMounted('generateProposal')
            ->assertActionDataSet([
                'sprints' => 4,
                'days' => 0,
                'hours' => 0,
            ])
            // The live Investment placeholder renders 4 x $3,000 straight away.
            ->assertMountedActionModalSee('$12,000');
    }

    public function test_a_client_and_a_brief_are_required(): void
    {
        Livewire::test(ListProposals::class)
            ->mountAction('generateProposal')
            ->callMountedAction()
            ->assertHasActionErrors(['client_id' => 'required', 'scope_prompt' => 'required']);
    }

    public function test_clients_are_searchable_by_name_company_and_email(): void
    {
        $client = Client::create([
            'name' => 'Dana Reyes',
            'email' => 'dana@northsidehvac.test',
            'company' => 'Northside HVAC',
        ]);

        $options = ListProposals::clientOptions(Client::all());

        $this->assertSame([$client->id => 'Dana Reyes — Northside HVAC'], $options);
    }

    public function test_a_client_without_a_company_is_labelled_by_name_alone(): void
    {
        $client = Client::create(['name' => 'Dana Reyes', 'email' => 'dana@example.test']);

        $this->assertSame('Dana Reyes', ListProposals::clientLabel($client));
    }

    public function test_the_investment_placeholder_totals_the_mix(): void
    {
        $summary = ListProposals::engagementSummary(fn (string $field) => match ($field) {
            'sprints' => 4,
            'days' => 10,
            'hours' => 20,
        })->toHtml();

        $this->assertStringContainsString('$28,000', $summary);
        $this->assertStringContainsString('4 sprints at $3,000 each = $12,000', $summary);
        $this->assertStringContainsString('10 days at $1,250 each = $12,500', $summary);
        $this->assertStringContainsString('20 hours at $175 each = $3,500', $summary);
        $this->assertStringContainsString('4 sprints — one investment row and one milestone each', $summary);
    }
}
