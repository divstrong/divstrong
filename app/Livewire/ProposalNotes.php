<?php

namespace App\Livewire;

use App\Models\ProposalNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProposalNotes extends Component
{
    use WithFileUploads;

    public int $proposalId;

    public string $newNote = '';

    public $attachment = null;

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required_without:attachment|nullable|min:1',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $data = [
            'proposal_id' => $this->proposalId,
            'user_id' => Auth::id(),
            'body' => $this->newNote ?: '',
        ];

        if ($this->attachment) {
            $data['attachment_path'] = $this->attachment->store('proposal-attachments', 'public');
            $data['attachment_name'] = $this->attachment->getClientOriginalName();
        }

        ProposalNote::create($data);

        $this->newNote = '';
        $this->attachment = null;
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function deleteNote(int $noteId): void
    {
        $note = ProposalNote::where('id', $noteId)
            ->where('proposal_id', $this->proposalId)
            ->where('user_id', Auth::id())
            ->first();

        if ($note) {
            if ($note->attachment_path) {
                Storage::disk('public')->delete($note->attachment_path);
            }
            $note->delete();
        }
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
