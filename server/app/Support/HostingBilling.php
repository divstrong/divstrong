<?php

namespace App\Support;

use App\Mail\HostingRenewalNotice;
use App\Models\HostingAccount;
use App\Models\HostingInvoice;
use App\Services\PayPalInvoicing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Renewal billing for hosting accounts: raise the PayPal invoice for the next term, send
 * our notice, remind, and roll the term forward once PayPal says it is paid.
 */
class HostingBilling
{
    public function __construct(private PayPalInvoicing $invoicing) {}

    /**
     * Raise and send the renewal invoice for the term after the current one.
     *
     * @param  array<int, string>  $emails
     *
     * @throws \RuntimeException when there is nothing sensible to invoice
     */
    public function raiseRenewal(HostingAccount $account, array $emails): HostingInvoice
    {
        $emails = array_values(array_unique(array_filter(array_map('trim', $emails))));

        if ($account->status !== HostingAccount::STATUS_ACTIVE) {
            throw new \RuntimeException('This hosting account is canceled.');
        }

        if ($emails === []) {
            throw new \RuntimeException('Add a billing email first — there is nobody to send the invoice to.');
        }

        if ((float) $account->term_amount <= 0) {
            throw new \RuntimeException('The term amount is $0 — set what this renewal costs first.');
        }

        $existing = $account->currentRenewal();

        if ($existing && ! $existing->isCanceled()) {
            throw new \RuntimeException('The renewal for ' . $existing->term_start->format('M j, Y') . ' has already been invoiced'
                . ($existing->isPaid() ? ' and paid.' : ' — send a reminder instead.'));
        }

        $start = $account->nextTermStart();
        $end = $account->nextTermEnd();
        // Due when the current term ends — or today, for a term that already lapsed, since an
        // invoice cannot be due before the day it was raised.
        $due = $account->term_end->copy()->max(now()->startOfDay());

        $created = $this->invoicing->create([
            'business_name' => $account->client?->company ?: $account->name,
            'reference' => $account->domain,
            'currency' => 'USD',
            'amount' => $account->term_amount,
            'due_date' => $due->toDateString(),
            'item_name' => 'Annual hosting · ' . $account->domain,
            'item_description' => 'Hosting, maintenance and support, ' . $start->format('M j, Y') . ' – ' . $end->format('M j, Y'),
            'note' => 'Thank you for extending your hosting with ' . config('hosting.business_name') . ' for another year.',
        ], $emails);

        $paymentUrl = $this->invoicing->send($created['id']);

        $invoice = DB::transaction(fn () => HostingInvoice::updateOrCreate(
            ['hosting_account_id' => $account->id, 'term_start' => $start->toDateString()],
            [
                'term_end' => $end->toDateString(),
                'due_date' => $due->toDateString(),
                'amount' => $account->term_amount,
                'currency' => 'USD',
                'status' => HostingInvoice::STATUS_SENT,
                'paypal_invoice_id' => $created['id'],
                'invoice_number' => $created['number'],
                'payment_url' => $paymentUrl,
                'recipients' => $emails,
                'sent_at' => now(),
                'paid_at' => null,
                'last_reminded_at' => null,
                'reminder_count' => 0,
                'paypal_response' => $created['response'],
            ],
        ));

        $this->mail($invoice->setRelation('account', $account), $emails, HostingRenewalNotice::INVOICE);

        return $invoice;
    }

    /** @param  array<int, string>  $emails */
    public function remind(HostingInvoice $invoice, array $emails): void
    {
        $emails = array_values(array_unique(array_filter(array_map('trim', $emails))));

        if (! $invoice->isOpen()) {
            throw new \RuntimeException('This invoice is ' . $invoice->status . ' — there is nothing to remind about.');
        }

        if ($emails === []) {
            throw new \RuntimeException('Add at least one email address.');
        }

        // Paid since the last look? Then a reminder would be an insult.
        if ($this->sync($invoice)) {
            throw new \RuntimeException('PayPal shows this invoice as paid — the term has been extended.');
        }

        $this->mail($invoice, $emails, HostingRenewalNotice::REMINDER);

        $invoice->forceFill([
            'last_reminded_at' => now(),
            'reminder_count' => $invoice->reminder_count + 1,
        ])->save();
    }

    /**
     * Ask PayPal where the invoice stands. Returns true when it is (now) paid.
     */
    public function sync(HostingInvoice $invoice): bool
    {
        if ($invoice->isPaid()) {
            return true;
        }

        if (! $invoice->paypal_invoice_id || ! $invoice->isOpen()) {
            return false;
        }

        $remote = $this->invoicing->get($invoice->paypal_invoice_id);
        $status = $remote['status'] ?? null;

        if (in_array($status, ['PAID', 'MARKED_AS_PAID'], true)) {
            $paidAt = $remote['payments']['transactions'][0]['payment_date'] ?? null;
            $this->markPaid($invoice, $paidAt ? \Illuminate\Support\Carbon::parse($paidAt) : now());

            return true;
        }

        if ($status === 'CANCELLED') {
            $invoice->forceFill(['status' => HostingInvoice::STATUS_CANCELED])->save();
        }

        return false;
    }

    /** Record the payment and move the account onto the term it paid for. */
    public function markPaid(HostingInvoice $invoice, ?\Illuminate\Support\Carbon $at = null): void
    {
        DB::transaction(function () use ($invoice, $at) {
            $invoice->forceFill(['status' => HostingInvoice::STATUS_PAID, 'paid_at' => $at ?? now()])->save();

            $account = $invoice->account;

            // Only ever forwards: a late sync of an old invoice must not pull the term back.
            if ($account->term_end->lt($invoice->term_end)) {
                $account->forceFill([
                    'term_start' => $invoice->term_start,
                    'term_end' => $invoice->term_end,
                ])->save();
            }
        });

        Log::info('Hosting renewal paid', [
            'account' => $invoice->hosting_account_id,
            'invoice' => $invoice->invoice_number,
            'amount' => $invoice->amount,
        ]);

        $this->notifyPaid($invoice);
    }

    public function cancel(HostingInvoice $invoice): void
    {
        if ($invoice->paypal_invoice_id && $invoice->isOpen()) {
            $this->invoicing->cancel($invoice->paypal_invoice_id);
        }

        $invoice->forceFill(['status' => HostingInvoice::STATUS_CANCELED])->save();
    }

    /** @param  array<int, string>  $emails */
    private function mail(HostingInvoice $invoice, array $emails, string $kind): void
    {
        Mail::to($emails)->send(new HostingRenewalNotice($invoice, $kind));
    }

    private function notifyPaid(HostingInvoice $invoice): void
    {
        $to = config('hosting.notify_email');

        if (blank($to)) {
            return;
        }

        try {
            $account = $invoice->account;

            Mail::raw(
                'Hosting renewal paid: ' . $account->domain . ' (' . ($account->client?->company ?: $account->name) . ")\n"
                . 'Amount: $' . number_format((float) $invoice->amount, 2) . "\n"
                . 'Invoice: #' . $invoice->invoice_number . "\n"
                . 'Term now runs ' . $invoice->term_start->format('M j, Y') . ' – ' . $invoice->term_end->format('M j, Y') . "\n\n"
                . url('/admin/hosting-accounts/' . $account->id . '/edit'),
                fn ($message) => $message->to($to)->subject('Hosting renewal paid: ' . $account->domain),
            );
        } catch (\Throwable $e) {
            Log::error('Hosting paid notification failed', ['error' => $e->getMessage()]);
        }
    }
}
