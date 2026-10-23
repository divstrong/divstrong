<?php

namespace App\Console\Commands;

use App\Models\ProposalMilestone;
use App\Support\MilestoneInvoicing;
use Illuminate\Console\Command;

/**
 * Checks every open milestone invoice with PayPal and records the ones that have been paid.
 *
 * The proposal page does the same for its own milestones when it is opened; this covers the
 * client who pays from PayPal's email and never comes back to the page.
 */
class SyncMilestoneInvoices extends Command
{
    protected $signature = 'proposals:sync-invoices';

    protected $description = 'Mark proposal milestones paid when their PayPal invoice has been paid';

    public function handle(MilestoneInvoicing $invoicing): int
    {
        $open = ProposalMilestone::query()
            ->whereNotNull('invoice_paypal_id')
            ->where('payment_status', '!=', 'paid')
            ->with('proposal.costItems')
            ->get();

        $paid = 0;

        foreach ($open as $milestone) {
            try {
                $paid += $invoicing->sync($milestone->proposal, $milestone) ? 1 : 0;
            } catch (\Throwable $e) {
                $this->warn("Milestone {$milestone->id}: {$e->getMessage()}");
            }
        }

        $this->line("Checked {$open->count()} open milestone invoice(s); {$paid} newly paid.");

        return self::SUCCESS;
    }
}
