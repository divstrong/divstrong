<?php

namespace App\Livewire;

use App\Models\ClientNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientNotes extends Component
{
    public int $clientId;

    public string $newNote = '';

    public function addNote(): void
    {
        $this->validate([
            'newNote' => 'required|min:1',
        ]);

        ClientNote::create([
            'client_id' => $this->clientId,
            'user_id' => Auth::id(),
            'body' => $this->newNote,
        ]);

        $this->newNote = '';
    }

    public function deleteNote(int $noteId): void
    {
        ClientNote::where('id', $noteId)
            ->where('client_id', $this->clientId)
            ->delete();
    }

    public function render()
    {
        $notes = ClientNote::where('client_id', $this->clientId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.client-notes', [
            'notes' => $notes,
        ]);
    }
}
