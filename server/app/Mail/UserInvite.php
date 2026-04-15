<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvite extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public ?string $notes = null,
        public ?string $token = null,
    ) {}

    public function envelope(): Envelope
    {
        $companyName = Setting::instance()->company_name ?? 'divStrong';

        return new Envelope(
            subject: "You're invited to {$companyName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.user-invite',
            with: [
                'user' => $this->user,
                'notes' => $this->notes,
                'inviteUrl' => url("/invite/{$this->token}"),
                'companyName' => Setting::instance()->company_name ?? 'divStrong',
            ],
        );
    }
}
