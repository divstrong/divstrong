<?php

namespace App\Livewire;

use App\Models\ProposalNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProposalNotes extends Component
{
    public int $proposalId;

    public string $newNote = '';

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|min:1',
        ]);

        ProposalNote::create([
            'proposal_id' => $this->proposalId,
            'user_id' => Auth::id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';
    }

    public function deleteNote(int $noteId): void
    {
        ProposalNote::where('id', $noteId)
            ->where('proposal_id', $this->proposalId)
            ->where('user_id', Auth::id())
            ->delete();
    }

    public function render()
    {
        $notes = ProposalNote::where('proposal_id', $this->proposalId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.proposal-notes', [
            'notes' => $notes,
            'currentUserId' => Auth::id(),
        ]);
    }
}
