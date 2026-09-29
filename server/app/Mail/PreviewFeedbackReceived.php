<?php

namespace App\Mail;

use App\Models\Prospect;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells us a prospect answered the questions on their preview page.
 *
 * Sent once when they finish (the "would you switch" answer, which is the last question),
 * carrying every answer and comment so far, and again only if they add a comment after
 * that — so a "no" followed by the reason arrives as two emails rather than one stale one.
 */
class PreviewFeedbackReceived extends Mailable
{
    use Queueable, SerializesModels;

    public const COMPLETED = 'completed';

    public const COMMENT = 'comment';

    public function __construct(
        public Prospect $prospect,
        public string $trigger = self::COMPLETED,
        public ?string $comment = null,
    ) {}

    public function envelope(): Envelope
    {
        $who = $this->prospect->company ?: ($this->prospect->name ?: $this->prospect->email);

        $subject = match (true) {
            $this->trigger === self::COMMENT => 'Preview comment: ' . $who,
            $this->prospect->interested === true => 'Interested: ' . $who . ' answered their preview',
            default => 'Preview feedback: ' . $who,
        };

        return new Envelope(
            // Reply-To is the prospect, so answering the notification reaches them.
            replyTo: filled($this->prospect->email)
                ? [new Address($this->prospect->email, $this->prospect->name ?: '')]
                : [],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.preview-feedback',
            with: [
                'prospect' => $this->prospect,
                'trigger' => $this->trigger,
                'comment' => $this->comment,
            ],
        );
    }
}
