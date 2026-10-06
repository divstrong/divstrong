<?php

namespace App\Mail;

use App\Models\HostingInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The client's renewal notice — or a reminder of it — with the button to PayPal's invoice.
 *
 * Signed by the billing department rather than a person: it goes out on a schedule, and a
 * reply should reach whoever is minding billing that week, not whoever wrote the code.
 */
class HostingRenewalNotice extends Mailable
{
    use Queueable, SerializesModels;

    public const INVOICE = 'invoice';

    public const REMINDER = 'reminder';

    public function __construct(public HostingInvoice $invoice, public string $kind = self::INVOICE) {}

    public function envelope(): Envelope
    {
        $domain = $this->invoice->account->domain;

        return new Envelope(
            from: new Address(config('mail.from.address'), config('hosting.billing_name')),
            replyTo: [new Address(config('hosting.billing_email'), config('hosting.billing_name'))],
            subject: $this->kind === self::REMINDER
                ? 'Reminder: hosting renewal for ' . $domain . ' ' . $this->dueWords()
                : 'Hosting renewal for ' . $domain . ' · invoice ' . $this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.hosting-renewal',
            with: [
                'invoice' => $this->invoice,
                'account' => $this->invoice->account,
                'isReminder' => $this->kind === self::REMINDER,
                'dueWords' => $this->dueWords(),
            ],
        );
    }

    /** "is due in 7 days", "is due today", "was due 3 days ago". */
    public function dueWords(): string
    {
        $days = (int) now()->startOfDay()->diffInDays($this->invoice->due_date, false);

        return match (true) {
            $days > 1 => "is due in {$days} days",
            $days === 1 => 'is due tomorrow',
            $days === 0 => 'is due today',
            $days === -1 => 'was due yesterday',
            default => 'was due ' . abs($days) . ' days ago',
        };
    }
}
