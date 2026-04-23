<?php

namespace App\Livewire;

use App\Enums\ProposalStatus;
use App\Filament\Resources\ProposalResource;
use App\Models\Proposal;
use Livewire\Component;

class ClientProposals extends Component
{
    public ?int $clientId = null;

    public function render()
    {
        $proposals = $this->clientId
            ? Proposal::where('client_id', $this->clientId)->orderBy('created_at', 'desc')->get()
            : collect();

        return view('livewire.client-proposals', [
            'proposals' => $proposals,
            'editUrl' => fn (Proposal $proposal) => ProposalResource\Pages\EditProposal::getUrl(['record' => $proposal]),
        ]);
    }
}
