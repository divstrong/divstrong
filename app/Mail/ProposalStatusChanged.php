<?php

namespace App\Mail;

use App\Models\Proposal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Proposal $proposal,
        public string $action,
    ) {}

    public function envelope(): Envelope
    {
        $status = ucfirst($this->action);

        return new Envelope(
            subject: "Proposal {$status}: {$this->proposal->project_title}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.proposal-status-changed',
            with: [
                'proposal' => $this->proposal,
                'action' => $this->action,
            ],
        );
    }
}
