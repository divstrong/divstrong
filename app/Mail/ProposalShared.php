<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalShared extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Proposal $proposal,
        public ?string $notes = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Proposal: {$this->proposal->project_title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proposal-shared',
            with: [
                'proposal' => $this->proposal,
                'notes' => $this->notes,
                'url' => $this->proposal->public_url,
            ],
        );
    }
}
