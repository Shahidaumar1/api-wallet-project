<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WebhookController;

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
