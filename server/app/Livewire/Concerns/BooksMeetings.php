<?php

namespace App\Livewire\Concerns;

use App\Models\Meeting;
use App\Models\Prospect;
use App\Support\Availability;
use App\Support\BookingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The booking calendar, shared by the preview page and the standalone /book page.
 *
 * Slot lists are computed per render rather than held in component state: a Livewire
 * component can sit open on a phone for an hour, and state would happily offer a slot
 * that was taken forty minutes ago. BookingService re-checks under a lock anyway, but
 * showing a slot that cannot be booked is a bad experience even when it is caught.
 */
trait BooksMeetings
{
    public string $timezone = '';

    public ?string $selectedDate = null;

    public ?string $selectedSlot = null;

    public string $bookingName = '';

    public string $bookingEmail = '';

    public string $bookingCompany = '';

    public string $bookingPhone = '';

    public string $bookingNotes = '';

    public ?int $bookedMeetingId = null;

    public ?string $bookingError = null;

    public function initBooking(?Prospect $prospect = null): void
    {
        $this->timezone = Availability::hostTimezone();

        if ($prospect) {
            $this->bookingName = (string) $prospect->name;
            $this->bookingEmail = (string) $prospect->email;
            $this->bookingCompany = (string) $prospect->company;
            $this->bookingPhone = (string) $prospect->phone;
        }
    }

    /** Set from the browser, so the times read in the visitor's own clock. */
    public function setTimezone(string $timezone): void
    {
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            $this->timezone = $timezone;
        }
    }

    /** @return Collection<int, array<string, mixed>> */
    public function getBookingDaysProperty(): Collection
    {
        return Availability::days();
    }

    /** @return Collection<int, array{value: string, label: string}> */
    public function getBookingSlotsProperty(): Collection
    {
        if (blank($this->selectedDate)) {
            return collect();
        }

        $tz = $this->viewerTimezone();

        return Availability::slotsOn($this->selectedDate)->map(fn (Carbon $slot) => [
            'value' => $slot->toIso8601String(),
            'label' => $slot->copy()->setTimezone($tz)->format('g:i A'),
        ]);
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedSlot = null;
        $this->bookingError = null;
    }

    public function selectSlot(string $slot): void
    {
        $this->selectedSlot = $slot;
        $this->bookingError = null;
    }

    public function book(): void
    {
        $this->bookingError = null;

        $this->validate([
            'selectedSlot' => 'required|string',
            'bookingName' => 'required|string|max:120',
            'bookingEmail' => 'required|email|max:190',
            'bookingCompany' => 'nullable|string|max:150',
            'bookingPhone' => 'nullable|string|max:40',
            'bookingNotes' => 'nullable|string|max:1000',
        ], [
            'selectedSlot.required' => 'Pick a time first.',
            'bookingName.required' => 'Please tell us who we are meeting.',
            'bookingEmail.required' => 'We need an email to send the invitation to.',
        ]);

        try {
            $meeting = BookingService::book(
                Carbon::parse($this->selectedSlot)->utc(),
                [
                    'name' => trim($this->bookingName),
                    'email' => trim($this->bookingEmail),
                    'company' => trim($this->bookingCompany) ?: null,
                    'phone' => trim($this->bookingPhone) ?: null,
                    'notes' => trim($this->bookingNotes) ?: null,
                    'timezone' => $this->viewerTimezone(),
                ],
                $this->bookingProspect(),
                $this->bookingSource(),
            );
        } catch (\RuntimeException $e) {
            // Almost always "somebody just took that slot". Clearing the selection sends
            // them back to a freshly computed list rather than re-offering a dead time.
            $this->bookingError = $e->getMessage();
            $this->selectedSlot = null;

            return;
        }

        $this->bookedMeetingId = $meeting->id;
        $this->afterBooked($meeting);
    }

    public function getBookedMeetingProperty(): ?Meeting
    {
        return $this->bookedMeetingId ? Meeting::find($this->bookedMeetingId) : null;
    }

    protected function viewerTimezone(): string
    {
        return $this->timezone ?: Availability::hostTimezone();
    }

    /** Overridden by the preview page, which knows who it is talking to. */
    protected function bookingProspect(): ?Prospect
    {
        return null;
    }

    protected function bookingSource(): string
    {
        return 'public';
    }

    protected function afterBooked(Meeting $meeting): void
    {
        //
    }
}
