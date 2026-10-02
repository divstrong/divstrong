<?php

namespace App\Console\Commands;

use App\Models\ProposalMilestone;
use App\Models\ProposalPayment;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Removes a recorded payment and puts its milestone back to unpaid.
 *
 * Written for payments captured while the site was pointed at PayPal's sandbox: PayPal said
 * "completed", so the milestone was marked paid and the notification went out, but no money
 * moved. This undoes exactly what the capture did — the payment row and the milestone's
 * paid state — and nothing else.
 *
 * It never talks to PayPal. A real payment needs refunding there first; this only corrects
 * our own books, which is why a payment that does not look like a sandbox one is refused
 * unless --real is passed.
 */
class RevertPayment extends Command
{
    protected $signature = 'payments:revert
        {id? : PayPal capture ID or order ID of the payment to revert}
        {--all-sandbox : Revert every completed payment that was captured in the sandbox}
        {--real : Allow reverting a payment that was NOT captured in the sandbox}
        {--force : Do not ask for confirmation}';

    protected $description = 'Delete a recorded payment and mark its milestone unpaid again';

    public function handle(): int
    {
        $payments = $this->payments();

        if ($payments === null) {
            $this->error('Give a capture or order ID, or use --all-sandbox.');

            return self::FAILURE;
        }

        if ($payments->isEmpty()) {
            $this->line('No matching payment found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Paid at', 'Amount', 'Project · Client', 'Milestone', 'Capture', 'Environment'],
            $payments->map(fn (ProposalPayment $p) => [
                $p->id,
                $p->paid_at?->format('M j, Y g:i A') ?? '—',
                '$' . number_format((float) $p->amount, 2),
                trim(($p->proposal?->project_title ?? ('#' . $p->proposal_id)) . ' · ' . ($p->proposal?->client_name ?? ''), ' ·'),
                $p->milestone?->title ?? '—',
                $p->paypal_capture_id ?? $p->paypal_order_id,
                $this->isSandbox($p) ? 'sandbox' : 'LIVE / unknown',
            ])->all(),
        );

        $real = $payments->reject(fn (ProposalPayment $p) => $this->isSandbox($p));

        if ($real->isNotEmpty() && ! $this->option('real')) {
            $this->error($real->count() . ' of these does not look like a sandbox payment, so real money may have moved.');
            $this->line('Refund it in PayPal first, then re-run with --real to remove the record.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Delete ' . $payments->count() . ' payment record(s) and mark the milestone(s) unpaid?')) {
            $this->line('Nothing changed.');

            return self::SUCCESS;
        }

        foreach ($payments as $payment) {
            DB::transaction(function () use ($payment) {
                $milestoneId = $payment->milestone_id;

                Log::warning('Payment reverted', [
                    'payment_id' => $payment->id,
                    'proposal_id' => $payment->proposal_id,
                    'milestone_id' => $milestoneId,
                    'amount' => $payment->amount,
                    'capture_id' => $payment->paypal_capture_id,
                    'order_id' => $payment->paypal_order_id,
                    'paid_at' => (string) $payment->paid_at,
                    'sandbox' => $this->isSandbox($payment),
                ]);

                $payment->delete();

                // Only unpaid again if nothing else genuinely paid this milestone.
                $stillPaid = $milestoneId && ProposalPayment::where('milestone_id', $milestoneId)
                    ->where('status', 'completed')
                    ->exists();

                if ($milestoneId && ! $stillPaid) {
                    ProposalMilestone::whereKey($milestoneId)->update([
                        'payment_status' => 'unpaid',
                        'paid_at' => null,
                    ]);
                }
            });

            $this->info('Reverted payment ' . ($payment->paypal_capture_id ?? $payment->paypal_order_id) . '.');
        }

        return self::SUCCESS;
    }

    /** @return Collection<int, ProposalPayment>|null */
    protected function payments(): ?Collection
    {
        $query = ProposalPayment::with(['proposal', 'milestone']);

        if ($this->option('all-sandbox')) {
            return $query->where('status', 'completed')->get()
                ->filter(fn (ProposalPayment $p) => $this->isSandbox($p))
                ->values();
        }

        $id = $this->argument('id');

        if (blank($id)) {
            return null;
        }

        return $query->where(fn ($q) => $q->where('paypal_capture_id', $id)->orWhere('paypal_order_id', $id))->get();
    }

    /**
     * PayPal's stored response carries links back to itself, on api.sandbox.paypal.com for
     * a test capture and api.paypal.com for a real one. No stored response means we cannot
     * tell, and that is treated as real.
     */
    protected function isSandbox(ProposalPayment $payment): bool
    {
        return str_contains(json_encode($payment->paypal_response ?? []), 'sandbox.paypal.com');
    }
}
