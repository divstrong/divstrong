<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceived;
use App\Exceptions\PayPalException;
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

            return response()->json([
                'error' => $e instanceof PayPalException
                    ? $e->userMessage()
                    : 'We were unable to start this payment. Please try again in a moment.',
                'reference' => $e instanceof PayPalException ? $e->debugId() : null,
            ], 500);
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

            Log::warning('PayPal capture not completed', [
                'order_id' => $orderId,
                'capture_status' => $captureStatus,
            ]);

            return response()->json([
                'status' => 'failed',
                'error' => 'PayPal did not complete this payment. Your card has not been charged — please try again.',
                'retryable' => true,
            ], 422);
        } catch (PayPalException $e) {
            Log::error('PayPal capture error', [
                'order_id' => $orderId,
                'issue' => $e->issue(),
                'debug_id' => $e->debugId(),
                'error' => $e->getMessage(),
            ]);

            // Leave the payment pending when PayPal says it already captured: the money
            // may well have moved, and marking it failed would hide a real payment.
            if ($e->issue() !== 'ORDER_ALREADY_CAPTURED') {
                $payment->update(['status' => 'failed']);
            }

            return response()->json([
                'status' => 'failed',
                'error' => $e->userMessage(),
                'reference' => $e->debugId(),
                'retryable' => $e->isRetryable(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('PayPal capture error', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            $payment->update(['status' => 'failed']);

            return response()->json([
                'status' => 'failed',
                'error' => 'We could not reach PayPal to complete this payment. Your card has not been charged — please try again in a moment.',
                'retryable' => true,
            ], 500);
        }
    }
}
