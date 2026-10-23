<?php

namespace App\Support;

use App\Mail\PaymentReceived;
use App\Models\Proposal;
use App\Models\ProposalMilestone;
use App\Models\ProposalPayment;
use App\Services\PayPalInvoicing;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Bill a proposal milestone with a real PayPal invoice.
 *
 * PayPal emails the invoice itself, so the client receives a proper invoice record they can
 * file and reconcile, and it sits in the PayPal account with everything else. Once PayPal
 * reports it paid, the milestone is marked paid and a ProposalPayment is recorded exactly as
 * an on-page payment would be — revenue reporting and the payment notice do not care which
 * route the money took.
 */
class MilestoneInvoicing
{
    /** A milestone's invoice can be re-sent (e.g. to a corrected address) this many times. */
    public const MAX_SENDS = 5;

    public function __construct(private PayPalInvoicing $invoicing) {}

    /** @return array{line1: ?string, line2: ?string, city: ?string, state: ?string, zip: ?string}|null */
    public static function addressOf(?\App\Models\Client $client): ?array
    {
        return $client && filled($client->address1) ? [
            'line1' => $client->address1,
            'line2' => $client->address2,
            'city' => $client->city,
            'state' => $client->state,
            'zip' => $client->zip,
        ] : null;
    }

    public static function amountFor(Proposal $proposal, ProposalMilestone $milestone): float
    {
        return round(($milestone->percentage / 100) * $proposal->total, 2);
    }

    /**
     * Raise and send the invoice for a milestone — or, if one is already open, replace it so
     * the new recipients get it and the old copy can no longer be paid twice.
     *
     * @param  array<int, string>  $emails
     *
     * @throws \RuntimeException with a message fit to show the client
     */
    public function send(Proposal $proposal, ProposalMilestone $milestone, array $emails): ProposalMilestone
    {
        if ($milestone->isPaid()) {
            throw new \RuntimeException('This milestone has already been paid.');
        }

        if ($milestone->invoice_send_count >= self::MAX_SENDS) {
            throw new \RuntimeException('This invoice has been sent several times already — please contact us and we will sort it out.');
        }

        $amount = self::amountFor($proposal, $milestone);

        if ($amount <= 0) {
            throw new \RuntimeException('There is no amount due for this milestone.');
        }

        // Paid in the meantime? Then the answer is "done", not another invoice.
        if ($milestone->invoice_paypal_id && $this->sync($proposal, $milestone)) {
            throw new \RuntimeException('This invoice has already been paid — thank you!');
        }

        if ($milestone->invoice_paypal_id) {
            $this->cancelQuietly($milestone);
        }

        $title = $proposal->project_title ?: 'Proposal ' . $proposal->uuid;
        $client = $proposal->client;

        // The customer billed is always the client assigned to the project, whoever asked for
        // the invoice; the addresses typed in are copied. Only when the client has no email on
        // file does the first typed address stand in as the billed one. Our own addresses are
        // dropped: PayPal already copies the account, and refuses its own address as a CC.
        $ownDomain = preg_replace('/^www\./', '', (string) parse_url((string) config('hosting.website'), PHP_URL_HOST));

        $emails = collect([$client?->email ?: $proposal->client_email])
            ->merge($emails)
            ->filter()
            ->map(fn (string $email) => strtolower(trim($email)))
            ->reject(fn (string $email) => $ownDomain !== '' && str_ends_with($email, '@' . $ownDomain))
            ->unique()
            ->values()
            ->all();

        if ($emails === []) {
            throw new \RuntimeException('Enter the client\'s email address the invoice should go to.');
        }

        $created = $this->invoicing->create([
            'business_name' => ($client?->company ?: $proposal->client_company) ?: null,
            'contact_name' => $client?->name ?: $proposal->client_name,
            'address' => self::addressOf($proposal->client),
            'reference' => 'Proposal ' . $proposal->uuid,
            'currency' => 'USD',
            'amount' => $amount,
            'due_date' => null,
            'item_name' => mb_substr($title . ' · ' . $milestone->title, 0, 200),
            'item_description' => number_format((float) $milestone->percentage, 0) . '% milestone payment — ' . url('/proposal/' . $proposal->uuid),
            'note' => 'Thank you for your business. Questions about this invoice? Reply to it or contact us at www.divstrong.com.',
        ], $emails);

        $url = $this->invoicing->send($created['id'], emailRecipient: true);

        $milestone->forceFill([
            'invoice_paypal_id' => $created['id'],
            'invoice_number' => $created['number'],
            'invoice_url' => $url,
            'invoice_recipients' => $emails,
            'invoice_sent_at' => now(),
            'invoice_send_count' => $milestone->invoice_send_count + 1,
        ])->save();

        Log::info('Milestone invoice sent', [
            'proposal' => $proposal->uuid,
            'milestone' => $milestone->id,
            'invoice' => $created['number'],
            'amount' => $amount,
            'to' => $emails,
        ]);

        return $milestone;
    }

    /**
     * Admin reset: cancel any open PayPal copy and clear the milestone back to "never invoiced",
     * send count included, so the next request raises a brand-new invoice.
     */
    public function reset(ProposalMilestone $milestone): ProposalMilestone
    {
        if ($milestone->isPaid()) {
            throw new \RuntimeException('This milestone has already been paid.');
        }

        if ($milestone->invoice_paypal_id) {
            $this->cancelQuietly($milestone);
        }

        $this->forget($milestone, resetCount: true);

        Log::info('Milestone invoice reset', ['milestone' => $milestone->id]);

        return $milestone;
    }

    /** Ask PayPal whether the milestone's invoice is paid; record it if so. True when paid. */
    public function sync(Proposal $proposal, ProposalMilestone $milestone): bool
    {
        if ($milestone->isPaid()) {
            return true;
        }

        if (! $milestone->invoice_paypal_id) {
            return false;
        }

        $remote = $this->invoicing->get($milestone->invoice_paypal_id);

        // Cancelled in PayPal (by hand, or superseded): it can never be paid, so stop pointing
        // the client at it and let a fresh one be sent.
        if (($remote['status'] ?? null) === 'CANCELLED') {
            $this->forget($milestone);

            return false;
        }

        if (! in_array($remote['status'] ?? null, ['PAID', 'MARKED_AS_PAID'], true)) {
            return false;
        }

        $transaction = $remote['payments']['transactions'][0] ?? [];
        $paidAt = isset($transaction['payment_date'])
            ? Carbon::parse($transaction['payment_date'])->setTimezone(config('app.timezone'))
            : now();

        $payment = DB::transaction(function () use ($proposal, $milestone, $remote, $transaction, $paidAt) {
            $milestone->forceFill(['payment_status' => 'paid', 'paid_at' => $paidAt])->save();

            // Keyed on the invoice id, so a second sync of the same invoice records nothing new.
            return ProposalPayment::firstOrCreate(
                ['paypal_order_id' => $milestone->invoice_paypal_id],
                [
                    'proposal_id' => $proposal->id,
                    'milestone_id' => $milestone->id,
                    'paypal_capture_id' => $transaction['payment_id'] ?? null,
                    'amount' => $remote['amount']['value'] ?? self::amountFor($proposal, $milestone),
                    'currency' => $remote['amount']['currency_code'] ?? 'USD',
                    'status' => 'completed',
                    'paid_at' => $paidAt,
                    'payer_email' => $remote['primary_recipients'][0]['billing_info']['email_address'] ?? null,
                    'paypal_response' => $remote,
                ],
            );
        });

        if ($payment->wasRecentlyCreated) {
            try {
                Mail::to('jim@divstrong.com')->send(new PaymentReceived($proposal, $payment->load('milestone')));
            } catch (\Throwable $e) {
                Log::error('Payment received notice failed', ['payment' => $payment->id, 'error' => $e->getMessage()]);
            }
        }

        return true;
    }

    private function cancelQuietly(ProposalMilestone $milestone): void
    {
        try {
            $this->invoicing->cancel($milestone->invoice_paypal_id);
        } catch (\Throwable $e) {
            // Already cancelled is the usual reason, and the outcome we wanted anyway.
            Log::warning('Could not cancel superseded milestone invoice', ['milestone' => $milestone->id, 'error' => $e->getMessage()]);
        }
    }

    private function forget(ProposalMilestone $milestone, bool $resetCount = false): void
    {
        $milestone->forceFill(array_merge([
            'invoice_paypal_id' => null,
            'invoice_number' => null,
            'invoice_url' => null,
            'invoice_recipients' => null,
            'invoice_sent_at' => null,
        ], $resetCount ? ['invoice_send_count' => 0] : []))->save();
    }
}
