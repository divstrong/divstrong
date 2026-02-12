<?php

namespace App\Livewire;

use App\Enums\ProposalStatus;
use App\Mail\ProposalShared;
use App\Mail\ProposalStatusChanged;
use App\Models\Client;
use App\Models\Proposal;
use App\Models\ProposalScopeItem;
use App\Models\ProposalTerm;
use App\Models\ScopeLibrary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
    public string $editingProposalDate = '';
    public string $editingIntroduction = '';
    public $coverImage = null;
    public string $editingCostNotes = '';
    public string $editingValidUntil = '';
    public string $editingChangeRequestContent = '';

    // Share modal
    public string $shareEmail = '';
    public string $shareNotes = '';
    public bool $shareSent = false;

    public function mount(string $uuid): void
    {
        $this->proposal = Proposal::where('uuid', $uuid)
            ->with(['scopeItems', 'costItems', 'milestones', 'terms', 'client'])
            ->firstOrFail();

        $this->accepted = $this->proposal->status === ProposalStatus::Accepted;
        $this->declined = $this->proposal->status === ProposalStatus::Declined;
        $this->converted = $this->proposal->status === ProposalStatus::Converted;
        $this->expired = $this->proposal->valid_until && $this->proposal->valid_until->isPast();

        $this->isAdmin = Auth::check();

        if ($this->isAdmin) {
            $this->editingClientId = $this->proposal->client_id;
            $this->editingProjectTitle = $this->proposal->project_title ?? '';
            $this->editingProposalDate = $this->proposal->proposal_date?->format('Y-m-d') ?? '';
            $this->editingIntroduction = $this->proposal->introduction ?? '';
            $this->editingCostNotes = $this->proposal->cost_notes ?? '';
            $this->editingValidUntil = $this->proposal->valid_until?->format('Y-m-d') ?? '';
            $this->editingChangeRequestContent = $this->proposal->change_request_content ?? '';
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

    public function updatedEditingProposalDate($value): void
    {
        if (! $this->isAdmin || blank($value)) return;

        $this->proposal->update(['proposal_date' => $value]);
        $this->proposal->refresh();
    }

    public function updatedEditingValidUntil($value): void
    {
        if (! $this->isAdmin) return;

        $this->proposal->update(['valid_until' => $value ?: null]);
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

        $this->shareSent = true;
        $this->shareNotes = '';
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
            $amount = ($percentage / 100) * $this->proposal->subtotal;
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

    public function render()
    {
        return view('livewire.proposal-view')
            ->layout('layouts.proposal', [
                'title' => $this->proposal->project_title,
            ]);
    }
}
