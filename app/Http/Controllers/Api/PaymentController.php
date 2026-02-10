<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Order;
use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Process payment for an order
     */
    public function processPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'api_key' => 'required|string',
                'order_id' => 'required|integer',
                'payment_method' => 'required|in:card,paypal,mobile_wallet,bank_transfer,stripe',
                'amount' => 'required|numeric|min:0.01',
                'currency' => 'nullable|string|size:3',
                'card_token' => 'nullable|string',
                'paypal_token' => 'nullable|string',
            ]);

            // Verify API key
            $apiClient = ApiClient::where('api_key', $validated['api_key'])
                ->where('is_active', true)
                ->first();

            if (!$apiClient) {
                return response()->json(['error' => 'Invalid API key'], 401);
            }

            // Get order
            $order = Order::find($validated['order_id']);
            if (!$order || $order->api_client_id !== $apiClient->id) {
                return response()->json(['error' => 'Order not found'], 404);
            }

            // Check payment method is allowed
            $paymentMethods = is_array($apiClient->payment_methods) 
                ? $apiClient->payment_methods 
                : json_decode($apiClient->payment_methods, true) ?? [];
                
            if (!in_array($validated['payment_method'], $paymentMethods)) {
                return response()->json(['error' => 'Payment method not allowed for this client'], 400);
            }

            // Create transaction record
            $transaction = Transaction::create([
                'order_id' => $validated['order_id'],
                'api_client_id' => $apiClient->id,
                'transaction_id' => 'TXN_' . Str::uuid(),
                'amount' => $validated['amount'],
                'currency' => $validated['currency'] ?? 'USD',
                'payment_method' => $validated['payment_method'],
                'status' => 'processing',
            ]);

            try {
                // Process payment based on method
                $result = $this->processPaymentByMethod(
                    $validated['payment_method'],
                    $transaction,
                    $validated
                );

                if ($result['success']) {
                    // Update transaction status
                    $transaction->update([
                        'status' => 'success',
                        'paid_at' => now(),
                        'response_data' => json_encode($result['data']),
                    ]);

                    // Update order status
                    $order->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);

                    // Trigger webhook
                    $this->triggerWebhook($order, $transaction);

                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'transaction_id' => $transaction->transaction_id,
                        'order_id' => $order->order_number,
                    ]);
                } else {
                    $transaction->update([
                        'status' => 'failed',
                        'error_message' => $result['message'],
                        'response_data' => json_encode($result['data'] ?? []),
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => $result['message'] ?? 'Payment failed',
                    ], 400);
                }
            } catch (\Exception $e) {
                $transaction->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Payment processing error',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transaction status
     */
    public function getStatus(Request $request, $transactionId)
    {
        $transaction = Transaction::where('transaction_id', $transactionId)
            ->with('order', 'apiClient')
            ->firstOrFail();

        // Verify API key belongs to the client
        $apiKey = $request->header('X-API-Key');
        $apiClient = ApiClient::where('api_key', $apiKey)->firstOrFail();

        if ($transaction->api_client_id !== $apiClient->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'transaction_id' => $transaction->transaction_id,
            'order_id' => $transaction->order->order_number,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'payment_method' => $transaction->payment_method,
            'paid_at' => $transaction->paid_at,
            'created_at' => $transaction->created_at,
        ]);
    }

    /**
     * Get transactions for API client
     */
    public function getTransactions(Request $request)
    {
        $apiKey = $request->header('X-API-Key');
        $apiClient = ApiClient::where('api_key', $apiKey)->firstOrFail();

        $transactions = Transaction::where('api_client_id', $apiClient->id)
            ->with('order')
            ->paginate(15);

        return response()->json($transactions);
    }

    /**
     * Refund a payment
     */
    public function refundPayment(Request $request)
    {
        $apiKey = $request->header('X-API-Key');
        $apiClient = ApiClient::where('api_key', $apiKey)->firstOrFail();

        $transaction = Transaction::where('transaction_id', $request->transaction_id)
            ->where('api_client_id', $apiClient->id)
            ->firstOrFail();

        if ($transaction->status !== 'success') {
            return response()->json(['error' => 'Can only refund successful transactions'], 400);
        }

        $transaction->update(['status' => 'refunded']);

        return response()->json([
            'success' => true,
            'message' => 'Refund processed',
            'transaction_id' => $transaction->transaction_id,
        ]);
    }

    /**
     * Process payment based on payment method
     */
    private function processPaymentByMethod($method, $transaction, $data)
    {
        switch ($method) {
            case 'stripe':
                return $this->processStripe($transaction, $data);
            case 'paypal':
                return $this->processPayPal($transaction, $data);
            case 'card':
                return $this->processCard($transaction, $data);
            case 'mobile_wallet':
                return $this->processMobileWallet($transaction, $data);
            case 'bank_transfer':
                return $this->processBankTransfer($transaction, $data);
            default:
                return ['success' => false, 'message' => 'Invalid payment method'];
        }
    }

    private function processStripe($transaction, $data)
    {
        // Stripe integration (placeholder)
        // In real scenario, integrate Stripe SDK
        return [
            'success' => true,
            'data' => [
                'provider' => 'stripe',
                'charge_id' => 'ch_' . Str::random(24),
            ]
        ];
    }

    private function processPayPal($transaction, $data)
    {
        // PayPal integration (placeholder)
        return [
            'success' => true,
            'data' => [
                'provider' => 'paypal',
                'transaction_id' => $data['paypal_token'] ?? 'PAY_' . Str::random(20),
            ]
        ];
    }

    private function processCard($transaction, $data)
    {
        // Card payment integration (placeholder)
        return [
            'success' => true,
            'data' => [
                'provider' => 'card',
                'last_four' => '****',
            ]
        ];
    }

    private function processMobileWallet($transaction, $data)
    {
        // Mobile wallet integration
        return [
            'success' => true,
            'data' => [
                'provider' => 'mobile_wallet',
                'reference' => 'MW_' . Str::random(20),
            ]
        ];
    }

    private function processBankTransfer($transaction, $data)
    {
        // Bank transfer integration
        return [
            'success' => true,
            'data' => [
                'provider' => 'bank_transfer',
                'reference' => 'BT_' . Str::random(20),
            ]
        ];
    }

    private function triggerWebhook($order, $transaction)
    {
        if (!$order->webhook_url) {
            return;
        }

        $payload = [
            'event' => 'payment.success',
            'order' => [
                'id' => $order->order_number,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
            ],
            'transaction' => [
                'id' => $transaction->transaction_id,
                'method' => $transaction->payment_method,
                'status' => $transaction->status,
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        // Queue webhook (use async in production)
        \Illuminate\Support\Facades\Http::post($order->webhook_url, $payload);
    }
}
