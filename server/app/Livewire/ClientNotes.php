<?php

namespace App\Livewire;

use App\Models\ClientNote;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ClientNotes extends Component
{
    public ?int $clientId = null;

    public string $newNote = '';

    public function addNote(): void
    {
        if (! $this->clientId) return;

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
        if (! $this->clientId) return;

        ClientNote::where('id', $noteId)
            ->where('client_id', $this->clientId)
            ->delete();
    }

    public function render()
    {
        $notes = $this->clientId
            ? ClientNote::where('client_id', $this->clientId)->with('user')->orderBy('created_at', 'desc')->get()
            : collect();

        return view('livewire.client-notes', [
            'notes' => $notes,
        ]);
    }
}
