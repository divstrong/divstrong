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

/** The prospect's confirmation, with the invite attached. */
class MeetingConfirmed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Meeting $meeting) {}

    public function envelope(): Envelope
    {
        $host = config('scheduling.host');

        return new Envelope(
            from: new Address(config('mail.from.address'), $host['name']),
            replyTo: [new Address($host['email'], $host['name'])],
            subject: 'Confirmed: your call with divStrong, ' . $this->meeting->startsAtLocal()->format('D M j \a\t g:i A'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meeting-confirmed',
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
