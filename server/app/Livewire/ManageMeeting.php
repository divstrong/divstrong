<?php

namespace App\Livewire;

use App\Models\Meeting;
use App\Support\BookingService;
use Livewire\Component;

/**
 * What the "reschedule or cancel" link in a confirmation opens.
 *
 * Rescheduling is deliberately cancel-then-book rather than a move: it frees the slot
 * immediately, sends both sides a calendar update that actually removes the old event,
 * and avoids a half-changed booking if they abandon the page midway.
 */
class ManageMeeting extends Component
{
    public Meeting $meeting;

    public bool $confirmingCancel = false;

    public function mount(string $token): void
    {
        $this->meeting = Meeting::where('token', $token)->firstOrFail();
    }

    public function cancel(): void
    {
        BookingService::cancel($this->meeting, by: 'prospect');
        $this->meeting->refresh();
        $this->confirmingCancel = false;
    }

    public function render()
    {
        return view('livewire.manage-meeting')
            ->layout('layouts.preview', ['title' => 'Your call with divStrong']);
    }
}
