<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Support\CalendarInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** The host's copy: who booked, when, and everything known about them. */
class MeetingBooked extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            // Reply-To is the prospect, so answering the notification reaches them.
            replyTo: [new Address($this->meeting->email, $this->meeting->name)],
            subject: 'Call booked: ' . ($this->meeting->company ?: $this->meeting->name)
                . ' · ' . $this->meeting->starts_at->copy()->setTimezone(config('scheduling.timezone'))->format('D M j, g:i A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meeting-booked',
            with: ['meeting' => $this->meeting, 'host' => config('scheduling.host')],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => CalendarInvite::request($this->meeting), CalendarInvite::filename($this->meeting))
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }
}
