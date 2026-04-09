<?php

namespace App\Mail;

use App\Models\RfpScreen;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RfpAnalysisComplete extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RfpScreen $rfpScreen) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Scanna Result: {$this->rfpScreen->rfp_name} — {$this->rfpScreen->score}/100",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.rfp-analysis-complete',
            with: [
                'rfpScreen' => $this->rfpScreen,
                'url' => url("/admin/screenah/{$this->rfpScreen->id}"),
            ],
        );
    }
}
