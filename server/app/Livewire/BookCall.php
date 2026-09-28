<?php

namespace App\Livewire;

use App\Livewire\Concerns\BooksMeetings;
use Livewire\Component;

/**
 * The standalone booking page at /book — where the older outreach emails' "Book 15
 * minutes" button can point, and where a canceled booking offers to rebook.
 */
class BookCall extends Component
{
    use BooksMeetings;

    public function mount(): void
    {
        $this->initBooking();
    }

    public function render()
    {
        return view('livewire.book-call')
            ->layout('layouts.preview', ['title' => 'Book a call · divStrong']);
    }
}
