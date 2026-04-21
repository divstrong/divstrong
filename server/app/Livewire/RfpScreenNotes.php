<?php

namespace App\Livewire;

use App\Models\RfpScreenNote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class RfpScreenNotes extends Component
{
    use WithFileUploads;

    public int $rfpScreenId;

    public string $newNote = '';

    public $attachment = null;

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required_without:attachment|nullable|min:1',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $data = [
            'rfp_screen_id' => $this->rfpScreenId,
            'user_id' => Auth::id(),
            'body' => $this->newNote ?: '',
        ];

        if ($this->attachment) {
            $data['attachment_path'] = $this->attachment->store('rfp-screen-notes', 'public');
            $data['attachment_name'] = $this->attachment->getClientOriginalName();
        }

        RfpScreenNote::create($data);

        $this->newNote = '';
        $this->attachment = null;
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function deleteNote(int $noteId): void
    {
        $note = RfpScreenNote::where('id', $noteId)
            ->where('rfp_screen_id', $this->rfpScreenId)
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
        $notes = RfpScreenNote::where('rfp_screen_id', $this->rfpScreenId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.rfp-screen-notes', [
            'notes' => $notes,
            'currentUserId' => Auth::id(),
        ]);
    }
}
