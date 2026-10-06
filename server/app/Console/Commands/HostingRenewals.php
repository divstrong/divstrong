<?php

namespace App\Console\Commands;

use App\Models\HostingAccount;
use App\Models\HostingInvoice;
use App\Support\HostingBilling;
use Illuminate\Console\Command;

/**
 * The daily hosting run.
 *
 *   1. Asks PayPal about every open renewal invoice, and extends the terms that were paid.
 *   2. When HOSTING_AUTO_INVOICE is on, raises the renewal invoice for each account whose
 *      term ends within the invoicing window and has not been invoiced yet.
 *
 * A term that lapsed long ago is never auto-invoiced: that is a book-keeping question for a
 * person (did they leave? was it paid by check?), not something to bill on a timer.
 */
class HostingRenewals extends Command
{
    protected $signature = 'hosting:renewals {--dry-run : List what would be invoiced without sending anything}';

    protected $description = 'Check renewal payments and send renewal invoices that are due';

    private const LAPSED_GRACE_DAYS = 30;

    public function handle(HostingBilling $billing): int
    {
        $open = HostingInvoice::where('status', HostingInvoice::STATUS_SENT)->with('account.client')->get();
        $paid = 0;

        foreach ($open as $invoice) {
            try {
                $paid += $billing->sync($invoice) ? 1 : 0;
            } catch (\Throwable $e) {
                $this->warn("Could not check #{$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        $this->line("Checked {$open->count()} open invoice(s); {$paid} newly paid.");

        $auto = config('hosting.auto_invoice');

        if (! $auto && ! $this->option('dry-run')) {
            $this->line('Automatic invoicing is off (HOSTING_AUTO_INVOICE=false).');

            return self::SUCCESS;
        }

        $window = now()->addDays((int) config('hosting.invoice_days_before'))->endOfDay();
        $oldest = now()->subDays(self::LAPSED_GRACE_DAYS)->startOfDay();

        $due = HostingAccount::active()
            ->where('auto_renew', true)
            ->whereDate('term_end', '<=', $window)
            ->with(['client', 'renewalInvoice'])
            ->orderBy('term_end')
            ->get()
            ->reject(fn (HostingAccount $account) => $account->currentRenewal() && ! $account->currentRenewal()->isCanceled());

        foreach ($due as $account) {
            $label = "{$account->domain} (term ends {$account->term_end->format('M j, Y')}, \${$account->term_amount})";

            if ($account->term_end->lt($oldest)) {
                $this->warn("Skipped {$label}: lapsed over " . self::LAPSED_GRACE_DAYS . ' days ago — send it by hand if it is still owed.');

                continue;
            }

            if ($account->billingEmails() === []) {
                $this->warn("Skipped {$label}: no billing email.");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("Would invoice {$label} → " . implode(', ', $account->billingEmails()));

                continue;
            }

            try {
                $invoice = $billing->raiseRenewal($account, $account->billingEmails());
                $this->info("Invoiced {$label} as #{$invoice->invoice_number}.");
            } catch (\Throwable $e) {
                $this->error("Failed {$label}: {$e->getMessage()}");
                report($e);
            }
        }

        if ($due->isEmpty()) {
            $this->line('No renewals due for invoicing.');
        }

        return self::SUCCESS;
    }
}
