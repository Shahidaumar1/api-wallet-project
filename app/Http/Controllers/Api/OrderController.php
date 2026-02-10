<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Create a new order
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'customer_email' => 'required|email',
            'customer_name' => 'required|string',
            'total_amount' => 'required|numeric|min:0.01',
            'currency' => 'nullable|string|size:3',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
            'webhook_url' => 'nullable|url',
        ]);

        // Verify API key
        $apiClient = ApiClient::where('api_key', $validated['api_key'])
            ->where('is_active', true)
            ->firstOrFail();

        // Create order
        $order = Order::create([
            'api_client_id' => $apiClient->id,
            'order_number' => 'ORD_' . Str::upper(Str::random(10)) . '_' . now()->timestamp,
            'customer_email' => $validated['customer_email'],
            'customer_name' => $validated['customer_name'],
            'total_amount' => $validated['total_amount'],
            'currency' => $validated['currency'] ?? 'USD',
            'description' => $validated['description'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
            'webhook_url' => $validated['webhook_url'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'amount' => $order->total_amount,
                'currency' => $order->currency,
                'created_at' => $order->created_at,
            ]
        ], 201);
    }

    /**
     * Get order details
     */
    public function getOrder(Request $request, $orderNumber)
    {
        $apiKey = $request->header('X-API-Key');
        $apiClient = ApiClient::where('api_key', $apiKey)
            ->where('is_active', true)
            ->firstOrFail();

        $order = Order::where('order_number', $orderNumber)
            ->where('api_client_id', $apiClient->id)
            ->with('transactions')
            ->firstOrFail();

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_email' => $order->customer_email,
            'total_amount' => $order->total_amount,
            'currency' => $order->currency,
            'status' => $order->status,
            'description' => $order->description,
            'paid_at' => $order->paid_at,
            'created_at' => $order->created_at,
            'transactions' => $order->transactions->map(function ($transaction) {
                return [
                    'transaction_id' => $transaction->transaction_id,
                    'amount' => $transaction->amount,
                    'payment_method' => $transaction->payment_method,
                    'status' => $transaction->status,
                    'paid_at' => $transaction->paid_at,
                ];
            }),
        ]);
    }

    /**
     * Get all orders for API client
     */
    public function getOrders(Request $request)
    {
        $apiKey = $request->header('X-API-Key');
        $apiClient = ApiClient::where('api_key', $apiKey)
            ->where('is_active', true)
            ->firstOrFail();

        $filter = $request->query('status');
        $query = Order::where('api_client_id', $apiClient->id);

        if ($filter) {
            $query->where('status', $filter);
        }

        $orders = $query->paginate(15);

        return response()->json($orders);
    }
}
