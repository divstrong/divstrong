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

/** Sent to both sides when a call is called off; carries the CANCEL invite. */
class MeetingCanceled extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting, public bool $forHost = false) {}

    public function envelope(): Envelope
    {
        $host = config('scheduling.host');

        return new Envelope(
            from: new Address(config('mail.from.address'), $host['name']),
            subject: 'Canceled: call on ' . $this->meeting->starts_at->copy()
                ->setTimezone($this->forHost ? config('scheduling.timezone') : $this->meeting->timezone)
                ->format('D M j, g:i A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meeting-canceled',
            with: [
                'meeting' => $this->meeting,
                'forHost' => $this->forHost,
                'host' => config('scheduling.host'),
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => CalendarInvite::cancel($this->meeting), CalendarInvite::filename($this->meeting))
                ->withMime('text/calendar; charset=UTF-8; method=CANCEL'),
        ];
    }
}
