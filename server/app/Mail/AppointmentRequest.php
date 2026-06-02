<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class AppointmentRequest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $name,
        public string $email,
        public string $projectType,
        public string $date,
        public string $time,
        public ?string $description = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Appointment Request from {$this->name}",
            replyTo: [new Address($this->email, $this->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointment-request',
        );
    }
}
