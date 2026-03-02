<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceived;
use App\Models\Proposal;
use App\Models\ProposalMilestone;
use App\Models\ProposalPayment;
use App\Services\PayPalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PayPalController extends Controller
{
    public function __construct(
        private PayPalService $paypal,
    ) {}

    public function createOrder(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'milestone_id' => 'required|integer',
        ]);

        $proposal = Proposal::where('uuid', $uuid)
            ->where('status', 'converted')
            ->with('costItems')
            ->firstOrFail();

        $milestone = ProposalMilestone::where('id', $request->milestone_id)
            ->where('proposal_id', $proposal->id)
            ->where('payment_status', 'unpaid')
            ->firstOrFail();

        $amount = round(($milestone->percentage / 100) * $proposal->total, 2);

        if ($amount <= 0) {
            return response()->json(['error' => 'Invalid payment amount'], 422);
        }

        try {
            $referenceId = "proposal-{$proposal->uuid}-ms-{$milestone->id}";
            $description = "{$proposal->project_title} - {$milestone->title}";

            $orderData = $this->paypal->createOrder(
                amount: $amount,
                currency: 'USD',
                referenceId: $referenceId,
                description: substr($description, 0, 127),
            );

            ProposalPayment::create([
                'proposal_id' => $proposal->id,
                'milestone_id' => $milestone->id,
                'paypal_order_id' => $orderData['id'],
                'amount' => $amount,
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            return response()->json(['id' => $orderData['id']]);
        } catch (\Throwable $e) {
            Log::error('PayPal create order error', [
                'proposal' => $uuid,
                'milestone' => $milestone->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to create payment'], 500);
        }
    }

    public function captureOrder(Request $request, string $uuid, string $orderId): JsonResponse
    {
        $proposal = Proposal::where('uuid', $uuid)
            ->where('status', 'converted')
            ->firstOrFail();

        $payment = ProposalPayment::where('paypal_order_id', $orderId)
            ->where('proposal_id', $proposal->id)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $captureData = $this->paypal->captureOrder($orderId);

            $captureStatus = $captureData['status'] ?? 'UNKNOWN';
            $capture = $captureData['purchase_units'][0]['payments']['captures'][0] ?? null;

            if ($captureStatus === 'COMPLETED' && $capture) {
                DB::transaction(function () use ($payment, $capture, $captureData) {
                    $payment->update([
                        'paypal_capture_id' => $capture['id'],
                        'status' => 'completed',
                        'paid_at' => now(),
                        'payer_email' => $captureData['payer']['email_address'] ?? null,
                        'paypal_response' => $captureData,
                    ]);

                    if ($payment->milestone_id) {
                        ProposalMilestone::where('id', $payment->milestone_id)->update([
                            'payment_status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }
                });

                $payment->load('milestone');

                Mail::to('jim@divstrong.com')
                    ->send(new PaymentReceived($proposal, $payment));

                return response()->json([
                    'status' => 'completed',
                    'capture_id' => $capture['id'],
                ]);
            }

            $payment->update([
                'status' => 'failed',
                'paypal_response' => $captureData,
            ]);

            return response()->json([
                'status' => 'failed',
                'details' => $captureData,
            ], 422);
        } catch (\Throwable $e) {
            Log::error('PayPal capture error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            $payment->update(['status' => 'failed']);

            return response()->json(['error' => 'Payment capture failed'], 500);
        }
    }
}
