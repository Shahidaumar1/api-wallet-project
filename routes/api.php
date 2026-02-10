<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WebhookController;

// API Welcome Route
Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to Payment Gateway API',
        'version' => '1.0.0',
        'status' => 'active',
        'documentation' => 'https://github.com/Shahidaumar1/api-wallet-project',
        'endpoints' => [
            'orders' => [
                'POST /api/orders/create' => 'Create a new order',
                'GET /api/orders/{orderNumber}' => 'Get order details',
                'GET /api/orders' => 'List all orders (authenticated)',
            ],
            'payments' => [
                'POST /api/payment/process' => 'Process payment',
                'GET /api/payment/status/{transactionId}' => 'Check payment status',
                'GET /api/transactions' => 'List transactions (authenticated)',
                'POST /api/refund' => 'Refund a payment (authenticated)',
            ],
            'webhooks' => [
                'POST /api/webhooks/stripe' => 'Stripe webhook',
                'POST /api/webhooks/paypal' => 'PayPal webhook',
                'POST /api/webhooks/mobile-wallet' => 'Mobile Wallet webhook',
                'POST /api/webhooks/bank' => 'Bank Transfer webhook',
            ]
        ],
        'authentication' => 'API Key required in request body or X-API-Key header',
        'support' => 'admin@api-wallet.local'
    ]);
});

// Public payment routes (require API key in header)
Route::post('/orders/create', [OrderController::class, 'create']);
Route::post('/payment/process', [PaymentController::class, 'processPayment']);
Route::get('/payment/status/{transactionId}', [PaymentController::class, 'getStatus']);
Route::get('/orders/{orderNumber}', [OrderController::class, 'getOrder']);

// Webhook routes (payment gateway callbacks)
Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe']);
Route::post('/webhooks/paypal', [WebhookController::class, 'handlePayPal']);
Route::post('/webhooks/mobile-wallet', [WebhookController::class, 'handleMobileWallet']);
Route::post('/webhooks/bank', [WebhookController::class, 'handleBankTransfer']);

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::get('/transactions', [PaymentController::class, 'getTransactions']);
    Route::get('/orders', [OrderController::class, 'getOrders']);
    Route::post('/refund', [PaymentController::class, 'refundPayment']);
});
