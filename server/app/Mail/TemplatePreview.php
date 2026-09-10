<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A test send of an email template, fired from the template editor's "Send Preview" button.
 *
 * Takes fully rendered HTML rather than a template key, because the point is to test the
 * draft currently in the editor — including unsaved changes — not whatever is in the database.
 *
 * The subject is prefixed [TEST] so a forwarded copy can never be mistaken for a live send.
 */
class TemplatePreview extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        // Neither $html nor $replyTo: Mailable already owns both, untyped, and redeclaring
        // one with a type is a fatal error.
        public string $bodyHtml,
        public ?string $replyToAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        $replyTo = $this->replyToAddress ?: config('mail.from.address');

        return new Envelope(
            from: new Address(config('mail.from.address'), config('app.name')),
            // Replies go to whoever asked for the test, not to a shared mailbox.
            replyTo: [new Address($replyTo)],
            subject: '[TEST] '.($this->subjectLine ?: 'divStrong email preview'),
        );
    }

    public function content(): Content
    {
        // Already rendered through the branded shell by the caller, so it ships as-is.
        return new Content(htmlString: $this->bodyHtml);
    }

    public function attachments(): array
    {
        return [];
    }
}
