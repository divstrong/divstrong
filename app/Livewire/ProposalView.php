<?php

namespace App\Livewire;

use App\Enums\ProposalStatus;
use App\Mail\ProposalStatusChanged;
use App\Models\Client;
use App\Models\Proposal;
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

    // Admin inline editing
    public bool $isAdmin = false;
    public ?int $editingClientId = null;
    public string $editingProjectTitle = '';
    public string $editingProposalDate = '';
    public $coverImage = null;

    public function mount(string $uuid): void
    {
        $this->proposal = Proposal::where('uuid', $uuid)
            ->with(['scopeItems', 'costItems', 'milestones', 'client'])
            ->firstOrFail();

        $this->accepted = $this->proposal->status === ProposalStatus::Accepted;
        $this->declined = $this->proposal->status === ProposalStatus::Declined;
        $this->expired = $this->proposal->valid_until && $this->proposal->valid_until->isPast();

        $this->isAdmin = Auth::check();

        if ($this->isAdmin) {
            $this->editingClientId = $this->proposal->client_id;
            $this->editingProjectTitle = $this->proposal->project_title ?? '';
            $this->editingProposalDate = $this->proposal->proposal_date?->format('Y-m-d') ?? '';
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

    public function getClientsProperty(): Collection
    {
        if (! $this->isAdmin) {
            return new Collection();
        }

        return Client::orderBy('name')->get();
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

        if ($this->proposal->user) {
            Mail::to($this->proposal->user->email)
                ->send(new ProposalStatusChanged($this->proposal, 'accepted'));
        }
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

        if ($this->proposal->user) {
            Mail::to($this->proposal->user->email)
                ->send(new ProposalStatusChanged($this->proposal, 'declined'));
        }
    }

    public function render()
    {
        return view('livewire.proposal-view')
            ->layout('layouts.proposal', [
                'title' => $this->proposal->project_title,
            ]);
    }
}
