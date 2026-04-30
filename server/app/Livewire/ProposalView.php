<?php

namespace App\Livewire;

use App\Enums\ProposalStatus;
use App\Mail\ProposalShared;
use App\Mail\ProposalStatusChanged;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalRoadmapPhase;
use App\Models\ProposalScopeItem;
use App\Models\PortfolioItem;
use App\Models\ProjectReference;
use App\Models\ProposalTerm;
use App\Models\ScopeLibrary;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProposalView extends Component
{
    use WithFileUploads;

    public Proposal $proposal;
    public string $signatureName = '';
    public string $signatureData = '';
    public bool $accepted = false;
    public bool $declined = false;
    public bool $expired = false;
    public bool $converted = false;

    // Change Request signature (client)
    public string $crSignatureName = '';
    public string $crSignatureData = '';

    // Terms & Conditions signature (client)
    public string $tcSignatureName = '';
    public string $tcSignatureData = '';

    // Admin inline editing
    public bool $isAdmin = false;
    public ?int $editingClientId = null;
    public string $editingProjectTitle = '';
    public string $editingRfpNumber = '';
    public string $editingProposalDate = '';
    public string $editingIntroduction = '';
    public $coverImage = null;
    public $overviewImage = null;
    public string $editingCostNotes = '';
    public string $editingValidUntil = '';
    public string $editingChangeRequestContent = '';

    // Roadmap editing
    public bool $editingRoadmapEnabled = false;
    public string $editingRoadmapTitle = '';
    public string $editingRoadmapSubtitle = '';
    public $editingRoadmapHoursPerSprint = 160;
    public $editingRoadmapMonths = 12;

    // Differentiator editing
    public bool $editingDifferentiatorEnabled = false;
    public string $editingDifferentiatorHeadline = '';
    public string $editingDifferentiatorAttribution = '';
    public $differentiatorBackground = null;

    // About Us toggle
    public bool $editingAboutEnabled = false;

    // Section visibility toggles (default true preserves prior behavior)
    public bool $editingInvestmentEnabled = true;
    public bool $editingMilestonesEnabled = true;
    public bool $editingChangesEnabled = true;
    public bool $editingTermsEnabled = true;

    // VPAT / accessibility statement toggle (opt-in, default off)
    public bool $editingVpatEnabled = false;

    // Past Performance / portfolio toggle (opt-in, default off)
    public bool $editingPerformanceEnabled = false;

    // References toggle (opt-in, default off)
    public bool $editingReferencesEnabled = false;

    // Team toggle (opt-in, default off)
    public bool $editingTeamEnabled = false;

    // Process section editing
    public bool $editingProcessEnabled = true;
    public string $editingProcessEyebrow = '';
    public string $editingProcessHeading = '';
    public string $editingProcessSubheading = '';
    public $processBackground = null;
    public array $editingProcessStages = [];
    public array $stageImages = [null, null, null, null, null];

    // Discount editing
    public bool $editingDiscountEnabled = false;
    public string $editingDiscountType = 'percent';
    public $editingDiscountValue = 0;

    // Payment state
    public ?int $payingMilestoneId = null;
    public bool $paymentSuccess = false;
    public string $paymentError = '';
    public string $lastCaptureId = '';

    // Share modal
    public string $shareEmail = '';
    public string $shareNotes = '';

    public function mount(string $uuid): void
    {
        $this->proposal = Proposal::where('uuid', $uuid)
            ->with(['scopeItems', 'costItems', 'milestones', 'terms', 'client', 'roadmapPhases', 'projectReferences', 'portfolioItems', 'teamMembers'])
            ->firstOrFail();

        $this->accepted = $this->proposal->status === ProposalStatus::Accepted;
        $this->declined = $this->proposal->status === ProposalStatus::Declined;
        $this->converted = $this->proposal->status === ProposalStatus::Converted;
        $this->expired = $this->proposal->valid_until && $this->proposal->valid_until->isPast();

        $this->isAdmin = Auth::check();

        if ($this->isAdmin) {
            $this->editingClientId = $this->proposal->client_id;
            $this->editingProjectTitle = $this->proposal->project_title ?? '';
            $this->editingRfpNumber = $this->proposal->rfp_number ?? '';
            $this->editingProposalDate = $this->proposal->proposal_date?->format('Y-m-d') ?? '';
            $this->editingIntroduction = $this->proposal->introduction ?? '';
            $this->editingCostNotes = $this->proposal->cost_notes ?? '';
            $this->editingValidUntil = $this->proposal->valid_until?->format('Y-m-d') ?? '';
            $this->editingChangeRequestContent = $this->proposal->change_request_content ?? '';
            $this->editingRoadmapEnabled = (bool) $this->proposal->roadmap_enabled;
            $this->editingRoadmapTitle = $this->proposal->roadmap_title ?? 'Operational Transformation Roadmap';
            $this->editingRoadmapSubtitle = $this->proposal->roadmap_subtitle ?? '';
            $this->editingRoadmapHoursPerSprint = $this->proposal->roadmap_hours_per_sprint ?? 160;
            $this->editingRoadmapMonths = $this->proposal->roadmap_months ?? 12;
            $this->editingDifferentiatorEnabled = (bool) $this->proposal->differentiator_enabled;
            $this->editingDifferentiatorHeadline = $this->proposal->differentiator_headline ?? '"We should have gone the custom route sooner!"';
            $this->editingDifferentiatorAttribution = $this->proposal->differentiator_attribution ?? '— Almost Every Client';
            $this->editingAboutEnabled = (bool) $this->proposal->about_enabled;
            $this->editingInvestmentEnabled = (bool) $this->proposal->investment_enabled;
            $this->editingMilestonesEnabled = (bool) $this->proposal->milestones_enabled;
            $this->editingChangesEnabled = (bool) $this->proposal->changes_enabled;
            $this->editingTermsEnabled = (bool) $this->proposal->terms_enabled;
            $this->editingVpatEnabled = (bool) $this->proposal->vpat_enabled;
            $this->editingPerformanceEnabled = (bool) $this->proposal->performance_enabled;
            $this->editingReferencesEnabled = (bool) $this->proposal->references_enabled;
            $this->editingTeamEnabled = (bool) $this->proposal->team_enabled;
            $this->editingProcessEnabled = (bool) $this->proposal->process_enabled;
            $this->editingProcessEyebrow = $this->proposal->process_eyebrow ?? 'Our Process';
            $this->editingProcessHeading = $this->proposal->process_heading ?? 'Ship early. Ship often. Level up together.';
            $this->editingProcessSubheading = $this->proposal->process_subheading ?? "We don't disappear for six months and hand you a finished product. We deliver something usable at every stage — you ride it, learn from it, and we iterate toward the end goal together.";
            $this->editingProcessStages = $this->proposal->process_stages_resolved;
            $this->editingDiscountEnabled = (bool) $this->proposal->discount_enabled;
            $this->editingDiscountType = $this->proposal->discount_type ?? 'percent';
            $this->editingDiscountValue = (float) ($this->proposal->discount_value ?? 0);
            $this->shareEmail = $this->proposal->client_email ?? '';
        }
    }

    public function updatedEditingClientId($value): void
    {
        if (! $this->isAdmin) return;

        $client = Client::find($value);
        if ($client) {
            $this->proposal->update([
                'client_id' => $client->id,
                'client_name' => $client->name,
                'client_email' => $client->email,
                'client_company' => $client->company,
                'client_domain' => $client->domain,
            ]);
            $this->proposal->refresh();
        }
    }

    public function updatedEditingProjectTitle($value): void
    {
        if (! $this->isAdmin || blank($value)) return;

        $this->proposal->update(['project_title' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingRfpNumber($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['rfp_number' => $value ?: null]);
        $this->proposal->refresh();
    }

    public function updatedEditingProposalDate($value): void
    {
        if (! $this->isAdmin || blank($value)) return;

        $this->proposal->update(['proposal_date' => $value]);
        $this->proposal->refresh();
    }

    #[On('update-proposal-date')]
    public function onUpdateProposalDate(string $date): void
    {
        $this->editingProposalDate = $date;
        $this->updatedEditingProposalDate($date);
    }

    public function updatedEditingValidUntil($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['valid_until' => $value ?: null]);
        $this->proposal->refresh();
        $this->expired = $this->proposal->valid_until && $this->proposal->valid_until->isPast();
    }

    #[On('update-valid-until')]
    public function onUpdateValidUntil(string $date): void
    {
        $this->editingValidUntil = $date;
        $this->updatedEditingValidUntil($date);
    }

    public function updatedEditingDiscountEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['discount_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingDiscountType($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['discount_type' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingDiscountValue($value): void
    {
        if (! $this->isAdmin) return;

        $value = max(0, (float) $value);
        if ($this->editingDiscountType === 'percent') {
            $value = min(100, $value);
        }

        $this->proposal->update(['discount_value' => $value]);
        $this->proposal->refresh();
    }

    public function saveIntroduction(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['introduction' => $this->editingIntroduction]);
        $this->proposal->refresh();
    }

    public function updatedCoverImage(): void
    {
        if (! $this->isAdmin) return;

        $this->validate([
            'coverImage' => 'image|max:5120',
        ]);

        // Delete old cover image if exists
        if ($this->proposal->cover_image) {
            Storage::disk('public')->delete($this->proposal->cover_image);
        }

        $path = $this->coverImage->store('proposal-covers', 'public');

        $this->proposal->update(['cover_image' => $path]);
        $this->proposal->refresh();
        $this->coverImage = null;
        $this->dispatch('cover-uploaded');
    }

    public function removeCoverImage(): void
    {
        if (! $this->isAdmin) return;

        if ($this->proposal->cover_image) {
            Storage::disk('public')->delete($this->proposal->cover_image);
            $this->proposal->update(['cover_image' => null]);
            $this->proposal->refresh();
        }
    }

    public function updatedOverviewImage(): void
    {
        if (! $this->isAdmin) return;

        $this->validate([
            'overviewImage' => 'image|max:10240',
        ]);

        if ($this->proposal->overview_image) {
            Storage::disk('public')->delete($this->proposal->overview_image);
        }

        $path = $this->overviewImage->store('proposal-overviews', 'public');

        $this->proposal->update(['overview_image' => $path]);
        $this->proposal->refresh();
        $this->overviewImage = null;
        $this->dispatch('overview-image-uploaded');
    }

    public function removeOverviewImage(): void
    {
        if (! $this->isAdmin) return;

        if ($this->proposal->overview_image) {
            Storage::disk('public')->delete($this->proposal->overview_image);
            $this->proposal->update(['overview_image' => null]);
            $this->proposal->refresh();
        }
    }

    public function shareProposal(): void
    {
        if (! $this->isAdmin) return;

        $this->validate([
            'shareEmail' => 'required|email',
            'shareNotes' => 'nullable|string|max:2000',
        ]);

        Mail::to($this->shareEmail)
            ->send(new ProposalShared($this->proposal, $this->shareNotes));

        if (! $this->proposal->sent_at) {
            $this->proposal->update([
                'status' => ProposalStatus::Sent,
                'sent_at' => now(),
            ]);
            $this->proposal->refresh();
        }

        $this->shareNotes = '';
        $this->dispatch('proposal-shared');
    }

    public function getClientsProperty(): Collection
    {
        if (! $this->isAdmin) {
            return new Collection();
        }

        return Client::orderBy('name')->get();
    }

    public function getScopeLibraryProperty(): Collection
    {
        if (! $this->isAdmin) {
            return new Collection();
        }

        return ScopeLibrary::where('is_active', true)->orderBy('category')->orderBy('sort_order')->get();
    }

    public function addScopeItems(array $ids): void
    {
        if (! $this->isAdmin) return;

        $maxSort = $this->proposal->scopeItems()->max('sort_order') ?? 0;

        $libraryItems = ScopeLibrary::whereIn('id', $ids)->orderBy('category')->orderBy('sort_order')->get();

        foreach ($libraryItems as $item) {
            $maxSort++;
            $this->proposal->scopeItems()->create([
                'category' => $item->category,
                'title' => $item->title,
                'description' => $item->description,
                'bullets' => $item->bullets,
                'sort_order' => $maxSort,
            ]);
        }

        $this->proposal->load('scopeItems');
    }

    public function updateScopeItem(int $id, string $title, string $description = '', array $bullets = []): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->scopeItems()->find($id);
        if ($item) {
            // Filter out empty bullets
            $bullets = array_values(array_filter($bullets, fn ($b) => trim($b) !== ''));
            $item->update([
                'title' => $title,
                'description' => $description ?: null,
                'bullets' => $bullets ?: null,
            ]);
            $this->proposal->load('scopeItems');
        }
    }

    public function duplicateScopeItem(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->scopeItems()->find($id);
        if ($item) {
            $this->proposal->scopeItems()->create([
                'category' => $item->category,
                'title' => $item->title,
                'description' => $item->description,
                'bullets' => $item->bullets,
                'sort_order' => $item->sort_order + 1,
            ]);

            // Re-sequence items after the duplicate to avoid sort_order conflicts
            $allItems = $this->proposal->scopeItems()->orderBy('sort_order')->get();
            foreach ($allItems as $index => $scopeItem) {
                $scopeItem->update(['sort_order' => $index]);
            }

            $this->proposal->load('scopeItems');
        }
    }

    public function deleteScopeItem(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->scopeItems()->find($id);
        if ($item) {
            $item->delete();
            $this->proposal->load('scopeItems');
        }
    }

    public function reorderScopeItems(array $orderedIds): void
    {
        if (! $this->isAdmin) return;

        foreach ($orderedIds as $index => $id) {
            $this->proposal->scopeItems()->where('id', $id)->update(['sort_order' => $index]);
        }

        $this->proposal->load('scopeItems');
    }

    public function reorderScopeCategories(array $orderedCategories): void
    {
        if (! $this->isAdmin) return;

        $sortOrder = 0;
        foreach ($orderedCategories as $category) {
            $items = $this->proposal->scopeItems()->where('category', $category)->orderBy('sort_order')->get();
            foreach ($items as $item) {
                $item->update(['sort_order' => $sortOrder]);
                $sortOrder++;
            }
        }

        $this->proposal->load('scopeItems');
    }

    // ---- Cost Item Methods ----

    public function saveCostNotes(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['cost_notes' => $this->editingCostNotes]);
        $this->proposal->refresh();
    }

    public function addCostItem(): void
    {
        if (! $this->isAdmin) return;

        $maxSort = $this->proposal->costItems()->max('sort_order') ?? 0;

        $this->proposal->costItems()->create([
            'description' => 'New Line Item',
            'quantity' => 1,
            'unit_price' => 0,
            'amount' => 0,
            'sort_order' => $maxSort + 1,
        ]);

        $this->proposal->load('costItems');
    }

    public function updateCostItem(int $id, string $description, int $quantity, float $unitPrice): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->costItems()->find($id);
        if ($item) {
            $item->update([
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'amount' => $quantity * $unitPrice,
            ]);
            $this->proposal->load('costItems');
        }
    }

    public function duplicateCostItem(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->costItems()->find($id);
        if ($item) {
            $this->proposal->costItems()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'amount' => $item->amount,
                'sort_order' => $item->sort_order + 1,
            ]);

            $allItems = $this->proposal->costItems()->orderBy('sort_order')->get();
            foreach ($allItems as $index => $costItem) {
                $costItem->update(['sort_order' => $index]);
            }

            $this->proposal->load('costItems');
        }
    }

    public function deleteCostItem(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->costItems()->find($id);
        if ($item) {
            $item->delete();
            $this->proposal->load('costItems');
        }
    }

    public function reorderCostItems(array $orderedIds): void
    {
        if (! $this->isAdmin) return;

        foreach ($orderedIds as $index => $id) {
            $this->proposal->costItems()->where('id', $id)->update(['sort_order' => $index]);
        }

        $this->proposal->load('costItems');
    }

    // ---- Milestone Methods ----

    public function addMilestone(): void
    {
        if (! $this->isAdmin) return;

        $maxSort = $this->proposal->milestones()->max('sort_order') ?? 0;

        $this->proposal->milestones()->create([
            'title' => 'New Milestone',
            'percentage' => 0,
            'amount' => 0,
            'sort_order' => $maxSort + 1,
        ]);

        $this->proposal->load('milestones');
    }

    public function updateMilestone(int $id, string $title, float $percentage): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->milestones()->find($id);
        if ($item) {
            $amount = ($percentage / 100) * $this->proposal->total;
            $item->update([
                'title' => $title,
                'percentage' => $percentage,
                'amount' => $amount,
            ]);
            $this->proposal->load('milestones');
        }
    }

    public function duplicateMilestone(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->milestones()->find($id);
        if ($item) {
            $this->proposal->milestones()->create([
                'title' => $item->title,
                'percentage' => $item->percentage,
                'amount' => $item->amount,
                'sort_order' => $item->sort_order + 1,
            ]);

            $allItems = $this->proposal->milestones()->orderBy('sort_order')->get();
            foreach ($allItems as $index => $ms) {
                $ms->update(['sort_order' => $index]);
            }

            $this->proposal->load('milestones');
        }
    }

    public function deleteMilestone(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->milestones()->find($id);
        if ($item) {
            $item->delete();
            $this->proposal->load('milestones');
        }
    }

    public function reorderMilestones(array $orderedIds): void
    {
        if (! $this->isAdmin) return;

        foreach ($orderedIds as $index => $id) {
            $this->proposal->milestones()->where('id', $id)->update(['sort_order' => $index]);
        }

        $this->proposal->load('milestones');
    }

    // ---- Roadmap Methods ----

    public function updatedEditingRoadmapEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['roadmap_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function saveRoadmapSettings(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update([
            'roadmap_title' => $this->editingRoadmapTitle,
            'roadmap_subtitle' => $this->editingRoadmapSubtitle,
            'roadmap_hours_per_sprint' => max(1, (int) $this->editingRoadmapHoursPerSprint),
            'roadmap_months' => max(1, (int) $this->editingRoadmapMonths),
        ]);
        $this->proposal->refresh();
    }

    // ---- Differentiator Methods ----

    public function updatedEditingDifferentiatorEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['differentiator_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingAboutEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['about_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingInvestmentEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['investment_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingMilestonesEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['milestones_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingChangesEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['changes_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingTermsEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['terms_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingVpatEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['vpat_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingPerformanceEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['performance_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingReferencesEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['references_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function getReferenceLibraryProperty(): Collection
    {
        if (! $this->isAdmin) {
            return new Collection();
        }

        return ProjectReference::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function attachReference(int $referenceId): void
    {
        if (! $this->isAdmin) return;

        $ref = ProjectReference::find($referenceId);
        if (! $ref) return;

        $maxSort = (int) \Illuminate\Support\Facades\DB::table('proposal_project_reference')
            ->where('proposal_id', $this->proposal->id)
            ->max('sort_order');
        $this->proposal->projectReferences()->syncWithoutDetaching([
            $referenceId => ['sort_order' => $maxSort + 1],
        ]);
        $this->proposal->load('projectReferences');
    }

    public function detachReference(int $referenceId): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->projectReferences()->detach($referenceId);
        $this->proposal->load('projectReferences');
    }

    public function getPortfolioLibraryProperty(): Collection
    {
        if (! $this->isAdmin) {
            return new Collection();
        }

        return PortfolioItem::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();
    }

    public function attachPortfolioItem(int $itemId): void
    {
        if (! $this->isAdmin) return;

        $item = PortfolioItem::find($itemId);
        if (! $item) return;

        $maxSort = (int) \Illuminate\Support\Facades\DB::table('portfolio_item_proposal')
            ->where('proposal_id', $this->proposal->id)
            ->max('sort_order');
        $this->proposal->portfolioItems()->syncWithoutDetaching([
            $itemId => ['sort_order' => $maxSort + 1],
        ]);
        $this->proposal->load('portfolioItems');
    }

    public function detachPortfolioItem(int $itemId): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->portfolioItems()->detach($itemId);
        $this->proposal->load('portfolioItems');
    }

    public function updatedEditingTeamEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['team_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function getTeamLibraryProperty(): Collection
    {
        if (! $this->isAdmin) {
            return new Collection();
        }

        return TeamMember::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function attachTeamMember(int $memberId): void
    {
        if (! $this->isAdmin) return;

        $member = TeamMember::find($memberId);
        if (! $member) return;

        $maxSort = (int) \Illuminate\Support\Facades\DB::table('proposal_team_member')
            ->where('proposal_id', $this->proposal->id)
            ->max('sort_order');
        $this->proposal->teamMembers()->syncWithoutDetaching([
            $memberId => ['sort_order' => $maxSort + 1],
        ]);
        $this->proposal->load('teamMembers');
    }

    public function detachTeamMember(int $memberId): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->teamMembers()->detach($memberId);
        $this->proposal->load('teamMembers');
    }

    public function saveDifferentiatorSettings(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update([
            'differentiator_headline' => $this->editingDifferentiatorHeadline,
            'differentiator_attribution' => $this->editingDifferentiatorAttribution,
        ]);
        $this->proposal->refresh();
    }

    public function updatedDifferentiatorBackground(): void
    {
        if (! $this->isAdmin) return;

        $this->validate([
            'differentiatorBackground' => 'image|max:10240',
        ]);

        if ($this->proposal->differentiator_background && ! str_starts_with($this->proposal->differentiator_background, 'images/')) {
            Storage::disk('public')->delete($this->proposal->differentiator_background);
        }

        $path = $this->differentiatorBackground->store('proposal-differentiators', 'public');

        $this->proposal->update(['differentiator_background' => $path]);
        $this->proposal->refresh();
        $this->differentiatorBackground = null;
        $this->dispatch('differentiator-background-uploaded');
    }

    public function removeDifferentiatorBackground(): void
    {
        if (! $this->isAdmin) return;

        if ($this->proposal->differentiator_background && ! str_starts_with($this->proposal->differentiator_background, 'images/')) {
            Storage::disk('public')->delete($this->proposal->differentiator_background);
        }
        $this->proposal->update(['differentiator_background' => null]);
        $this->proposal->refresh();
    }

    // ---- Process Section Methods ----

    public function updatedEditingProcessEnabled($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['process_enabled' => $value]);
        $this->proposal->refresh();
    }

    public function saveProcessSettings(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update([
            'process_eyebrow' => $this->editingProcessEyebrow ?: null,
            'process_heading' => $this->editingProcessHeading ?: null,
            'process_subheading' => $this->editingProcessSubheading ?: null,
        ]);
        $this->proposal->refresh();
    }

    public function updateProcessStage(int $index, string $label, string $caption): void
    {
        if (! $this->isAdmin) return;
        if ($index < 0 || $index > 4) return;

        $stages = $this->proposal->process_stages_resolved;
        $stages[$index]['label'] = $label;
        $stages[$index]['caption'] = $caption;

        $this->proposal->update(['process_stages' => array_values($stages)]);
        $this->proposal->refresh();
        $this->editingProcessStages = $this->proposal->process_stages_resolved;
    }

    public function updatedProcessBackground(): void
    {
        if (! $this->isAdmin) return;

        $this->validate([
            'processBackground' => 'image|max:10240',
        ]);

        if ($this->proposal->process_background && ! str_starts_with($this->proposal->process_background, 'images/')) {
            Storage::disk('public')->delete($this->proposal->process_background);
        }

        $path = $this->processBackground->store('proposal-process', 'public');

        $this->proposal->update(['process_background' => $path]);
        $this->proposal->refresh();
        $this->processBackground = null;
        $this->dispatch('process-background-uploaded');
    }

    public function removeProcessBackground(): void
    {
        if (! $this->isAdmin) return;

        if ($this->proposal->process_background && ! str_starts_with($this->proposal->process_background, 'images/')) {
            Storage::disk('public')->delete($this->proposal->process_background);
        }
        $this->proposal->update(['process_background' => null]);
        $this->proposal->refresh();
    }

    public function updatedStageImages($value, $key): void
    {
        if (! $this->isAdmin) return;

        $index = (int) $key;
        if ($index < 0 || $index > 4) return;

        $this->validate([
            "stageImages.$index" => 'image|max:5120',
        ]);

        $stages = $this->proposal->process_stages_resolved;
        $current = $stages[$index]['image'] ?? null;
        if ($current && ! str_starts_with($current, 'images/')) {
            Storage::disk('public')->delete($current);
        }

        $path = $this->stageImages[$index]->store('proposal-process-stages', 'public');
        $stages[$index]['image'] = $path;

        $this->proposal->update(['process_stages' => array_values($stages)]);
        $this->proposal->refresh();
        $this->stageImages[$index] = null;
        $this->editingProcessStages = $this->proposal->process_stages_resolved;
        $this->dispatch('process-stage-image-uploaded', index: $index);
    }

    public function removeStageImage(int $index): void
    {
        if (! $this->isAdmin) return;
        if ($index < 0 || $index > 4) return;

        $stages = $this->proposal->process_stages_resolved;
        $current = $stages[$index]['image'] ?? null;
        if ($current && ! str_starts_with($current, 'images/')) {
            Storage::disk('public')->delete($current);
        }

        $defaults = Proposal::defaultProcessStages();
        $stages[$index]['image'] = $defaults[$index]['image'];

        $this->proposal->update(['process_stages' => array_values($stages)]);
        $this->proposal->refresh();
        $this->editingProcessStages = $this->proposal->process_stages_resolved;
    }

    public function addRoadmapPhase(): void
    {
        if (! $this->isAdmin) return;

        $maxSort = $this->proposal->roadmapPhases()->max('sort_order') ?? -1;
        $colors = ['#10B981', '#F59E0B', '#EF4444', '#3B82F6', '#8B5CF6', '#EC4899', '#06B6D4'];
        $colorIndex = $this->proposal->roadmapPhases()->count() % count($colors);

        $this->proposal->roadmapPhases()->create([
            'title' => 'New Phase',
            'subtitle' => '',
            'color' => $colors[$colorIndex],
            'icon' => 'clipboard',
            'duration_weeks' => 4,
            'hours' => $this->proposal->roadmap_hours_per_sprint ?? 160,
            'sort_order' => $maxSort + 1,
        ]);

        $this->proposal->load('roadmapPhases');
    }

    public function updateRoadmapPhase(int $id, string $title, ?string $subtitle, ?string $description, string $color, string $icon, int $durationWeeks, ?int $hours): void
    {
        if (! $this->isAdmin) return;

        $phase = $this->proposal->roadmapPhases()->find($id);
        if ($phase) {
            $phase->update([
                'title' => $title,
                'subtitle' => $subtitle,
                'description' => $description,
                'color' => $color,
                'icon' => $icon,
                'duration_weeks' => max(1, $durationWeeks),
                'hours' => $hours,
            ]);
            $this->proposal->load('roadmapPhases');
        }
    }

    public function duplicateRoadmapPhase(int $id): void
    {
        if (! $this->isAdmin) return;

        $phase = $this->proposal->roadmapPhases()->find($id);
        if ($phase) {
            $this->proposal->roadmapPhases()->create([
                'title' => $phase->title,
                'subtitle' => $phase->subtitle,
                'description' => $phase->description,
                'color' => $phase->color,
                'icon' => $phase->icon,
                'duration_weeks' => $phase->duration_weeks,
                'hours' => $phase->hours,
                'sort_order' => $phase->sort_order + 1,
            ]);

            $allPhases = $this->proposal->roadmapPhases()->orderBy('sort_order')->get();
            foreach ($allPhases as $index => $p) {
                $p->update(['sort_order' => $index]);
            }

            $this->proposal->load('roadmapPhases');
        }
    }

    public function deleteRoadmapPhase(int $id): void
    {
        if (! $this->isAdmin) return;

        $phase = $this->proposal->roadmapPhases()->find($id);
        if ($phase) {
            $phase->delete();
            $this->proposal->load('roadmapPhases');
        }
    }

    public function reorderRoadmapPhases(array $orderedIds): void
    {
        if (! $this->isAdmin) return;

        foreach ($orderedIds as $index => $id) {
            $this->proposal->roadmapPhases()->where('id', $id)->update(['sort_order' => $index]);
        }

        $this->proposal->load('roadmapPhases');
    }

    // ---- Change Request Methods ----

    public function saveChangeRequestContent(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['change_request_content' => $this->editingChangeRequestContent]);
        $this->proposal->refresh();
    }

    public function signChangeRequest(): void
    {
        if ($this->isAdmin || $this->proposal->cr_signed_at) return;

        $this->validate([
            'crSignatureName' => 'required|string|max:255',
            'crSignatureData' => 'required|string',
        ]);

        $this->proposal->update([
            'cr_signature_name' => $this->crSignatureName,
            'cr_signature_data' => $this->crSignatureData,
            'cr_signed_at' => now(),
        ]);

        $this->proposal->refresh();
    }

    // ---- Terms & Conditions Methods ----

    public function addTerm(): void
    {
        if (! $this->isAdmin) return;

        $maxSort = $this->proposal->terms()->max('sort_order') ?? 0;

        $this->proposal->terms()->create([
            'content' => 'New term',
            'sort_order' => $maxSort + 1,
        ]);

        $this->proposal->load('terms');
    }

    public function updateTerm(int $id, string $content): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->terms()->find($id);
        if ($item) {
            $item->update(['content' => $content]);
            $this->proposal->load('terms');
        }
    }

    public function duplicateTerm(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->terms()->find($id);
        if ($item) {
            $this->proposal->terms()->create([
                'content' => $item->content,
                'sort_order' => $item->sort_order + 1,
            ]);

            $allItems = $this->proposal->terms()->orderBy('sort_order')->get();
            foreach ($allItems as $index => $term) {
                $term->update(['sort_order' => $index]);
            }

            $this->proposal->load('terms');
        }
    }

    public function deleteTerm(int $id): void
    {
        if (! $this->isAdmin) return;

        $item = $this->proposal->terms()->find($id);
        if ($item) {
            $item->delete();
            $this->proposal->load('terms');
        }
    }

    public function reorderTerms(array $orderedIds): void
    {
        if (! $this->isAdmin) return;

        foreach ($orderedIds as $index => $id) {
            $this->proposal->terms()->where('id', $id)->update(['sort_order' => $index]);
        }

        $this->proposal->load('terms');
    }

    public function signTerms(): void
    {
        if ($this->isAdmin || $this->proposal->tc_signed_at) return;

        $this->validate([
            'tcSignatureName' => 'required|string|max:255',
            'tcSignatureData' => 'required|string',
        ]);

        $this->proposal->update([
            'tc_signature_name' => $this->tcSignatureName,
            'tc_signature_data' => $this->tcSignatureData,
            'tc_signed_at' => now(),
        ]);

        $this->proposal->refresh();
    }

    // ---- Approval Methods ----

    public function approveProposal(): void
    {
        if ($this->isAdmin || $this->converted || $this->declined) return;
        if (! $this->proposal->tc_signed_at) return;

        $this->proposal->update([
            'status' => ProposalStatus::Converted,
            'accepted_at' => now(),
            'accepted_ip' => request()->ip(),
        ]);

        $this->converted = true;

        Mail::to('jim@divstrong.com')
            ->send(new ProposalStatusChanged($this->proposal, 'converted'));
    }

    public function acceptProposal(): void
    {
        if ($this->accepted || $this->declined || $this->expired) {
            return;
        }

        $this->validate([
            'signatureName' => 'required|string|max:255',
            'signatureData' => 'required|string',
        ]);

        $this->proposal->update([
            'status' => ProposalStatus::Accepted,
            'accepted_at' => now(),
            'accepted_ip' => request()->ip(),
            'signature_name' => $this->signatureName,
            'signature_data' => $this->signatureData,
        ]);

        $this->accepted = true;

        Mail::to('jim@divstrong.com')
            ->send(new ProposalStatusChanged($this->proposal, 'accepted'));
    }

    public function resetProposalStatus(): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update([
            'status' => ProposalStatus::Draft,
            'accepted_at' => null,
            'accepted_ip' => null,
            'declined_at' => null,
            'signature_name' => null,
            'signature_data' => null,
            'cr_signature_name' => null,
            'cr_signature_data' => null,
            'cr_signed_at' => null,
            'tc_signature_name' => null,
            'tc_signature_data' => null,
            'tc_signed_at' => null,
        ]);

        $this->proposal->refresh();
        $this->accepted = false;
        $this->declined = false;
        $this->converted = false;
    }

    public function declineProposal(): void
    {
        if ($this->accepted || $this->declined) {
            return;
        }

        $this->proposal->update([
            'status' => ProposalStatus::Declined,
            'declined_at' => now(),
        ]);

        $this->declined = true;

        Mail::to('jim@divstrong.com')
            ->send(new ProposalStatusChanged($this->proposal, 'declined'));
    }

    // ---- Payment Methods ----

    public function selectMilestoneForPayment(int $milestoneId): void
    {
        if (! $this->converted) return;

        $milestone = $this->proposal->milestones()->where('id', $milestoneId)->first();
        if (! $milestone || $milestone->payment_status === 'paid') return;

        $this->payingMilestoneId = $milestoneId;
        $this->paymentSuccess = false;
        $this->paymentError = '';

        $this->dispatch('init-paypal-payment', milestoneId: $milestoneId);
    }

    public function cancelPayment(): void
    {
        $this->payingMilestoneId = null;
        $this->paymentSuccess = false;
        $this->paymentError = '';
    }

    public function markMilestonePaid(int $milestoneId, string $captureId): void
    {
        $this->proposal->load('milestones');
        $this->payingMilestoneId = null;
        $this->paymentSuccess = true;
        $this->lastCaptureId = $captureId;
        $this->paymentError = '';
    }

    public function paymentFailed(string $error): void
    {
        $this->paymentError = $error;
        $this->paymentSuccess = false;
    }

    public function render()
    {
        return view('livewire.proposal-view')
            ->layout('layouts.proposal', [
                'title' => $this->proposal->project_title,
            ]);
    }
}
