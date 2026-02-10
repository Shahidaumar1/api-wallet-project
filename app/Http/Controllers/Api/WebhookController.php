<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Order;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /**
     * Handle Stripe webhook
     */
    public function handleStripe(Request $request)
    {
        $payload = $request->all();

        // Verify Stripe signature (in production)
        // $this->verifyStripeSignature($request);

        if ($payload['type'] === 'charge.succeeded') {
            $chargeId = $payload['data']['object']['id'];
            
            $transaction = Transaction::where('response_data->charge_id', $chargeId)->first();

            if ($transaction) {
                $this->updateTransactionSuccess($transaction);
            }
        } elseif ($payload['type'] === 'charge.failed') {
            $chargeId = $payload['data']['object']['id'];
            
            $transaction = Transaction::where('response_data->charge_id', $chargeId)->first();

            if ($transaction) {
                $this->updateTransactionFailed($transaction, $payload['data']['object']['failure_message'] ?? 'Payment failed');
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle PayPal webhook
     */
    public function handlePayPal(Request $request)
    {
        $payload = $request->all();

        if ($payload['event_type'] === 'CHECKOUT.ORDER.COMPLETED') {
            $orderId = $payload['resource']['id'];

            $transaction = Transaction::where('response_data->transaction_id', $orderId)->first();

            if ($transaction) {
                $this->updateTransactionSuccess($transaction);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle Mobile Wallet webhook
     */
    public function handleMobileWallet(Request $request)
    {
        $payload = $request->all();

        $reference = $payload['reference'] ?? null;

        if ($payload['status'] === 'success' && $reference) {
            $transaction = Transaction::where('response_data->reference', $reference)->first();

            if ($transaction) {
                $this->updateTransactionSuccess($transaction);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Handle Bank Transfer webhook
     */
    public function handleBankTransfer(Request $request)
    {
        $payload = $request->all();

        $reference = $payload['reference'] ?? null;

        if ($payload['status'] === 'confirmed' && $reference) {
            $transaction = Transaction::where('response_data->reference', $reference)->first();

            if ($transaction) {
                $this->updateTransactionSuccess($transaction);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Update transaction as successful
     */
    private function updateTransactionSuccess(Transaction $transaction)
    {
        $transaction->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Update order status
        $order = $transaction->order;
        if ($order) {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            // Notify the client through webhook
            $this->notifyClient($order, $transaction);
        }
    }

    /**
     * Update transaction as failed
     */
    private function updateTransactionFailed(Transaction $transaction, $errorMessage = 'Payment failed')
    {
        $transaction->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        // Update order status
        $order = $transaction->order;
        if ($order) {
            $order->update(['status' => 'failed']);
        }
    }

    /**
     * Notify client via webhook
     */
    private function notifyClient(Order $order, Transaction $transaction)
    {
        if (!$order->webhook_url) {
            return;
        }

        $payload = [
            'event' => 'payment.completed',
            'order' => [
                'id' => $order->order_number,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'status' => $order->status,
            ],
            'transaction' => [
                'id' => $transaction->transaction_id,
                'method' => $transaction->payment_method,
                'status' => $transaction->status,
                'paid_at' => $transaction->paid_at,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            \Illuminate\Support\Facades\Http::post($order->webhook_url, $payload);
        } catch (\Exception $e) {
            // Log webhook failure
            \Log::error('Webhook notification failed: ' . $e->getMessage());
        }
    }
}
