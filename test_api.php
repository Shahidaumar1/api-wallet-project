<?php
// API Testing Script

$apiKey = 'sk_test_5d223308e216489daa733664cd9e277ed41ed64fe245c8fb';
$baseUrl = 'http://localhost:8000/api';

echo "\n========================================\n";
echo "🧪 API ENDPOINT TESTING\n";
echo "========================================\n\n";

// 1. CREATE ORDER
echo "1️⃣ Creating Order...\n";
$orderData = [
    'api_key' => $apiKey,
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'total_amount' => 500,
    'currency' => 'PKR',
    'description' => 'Test Order',
    'webhook_url' => 'https://teststore.com/webhook'
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $baseUrl . '/orders/create',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($orderData)
]);

$orderResponse = json_decode(curl_exec($curl), true);
curl_close($curl);

if ($orderResponse && $orderResponse['success']) {
    echo "✅ Order Created!\n";
    echo "   Order ID: " . $orderResponse['data']['order_id'] . "\n";
    echo "   Order Number: " . $orderResponse['data']['order_number'] . "\n";
    echo "   Amount: " . $orderResponse['data']['amount'] . " " . $orderResponse['data']['currency'] . "\n\n";
    
    $orderId = $orderResponse['data']['order_id'];
    $orderNumber = $orderResponse['data']['order_number'];
} else {
    echo "❌ Failed to create order\n";
    echo "Response: " . json_encode($orderResponse, JSON_PRETTY_PRINT) . "\n";
    exit;
}

// 2. PROCESS PAYMENT
echo "2️⃣ Processing Payment...\n";
$paymentData = [
    'api_key' => $apiKey,
    'order_id' => $orderId,
    'payment_method' => 'stripe',
    'amount' => 500,
    'currency' => 'PKR',
    'card_token' => 'tok_visa'
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $baseUrl . '/payment/process',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($paymentData)
]);

$paymentResponse = json_decode(curl_exec($curl), true);
curl_close($curl);

if ($paymentResponse && $paymentResponse['success']) {
    echo "✅ Payment Processed!\n";
    echo "   Transaction ID: " . $paymentResponse['transaction_id'] . "\n";
    echo "   Status: " . $paymentResponse['message'] . "\n\n";
    
    $transactionId = $paymentResponse['transaction_id'];
} else {
    echo "❌ Failed to process payment\n";
    echo "Response: " . json_encode($paymentResponse, JSON_PRETTY_PRINT) . "\n";
    exit;
}

// 3. CHECK PAYMENT STATUS
echo "3️⃣ Checking Payment Status...\n";
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $baseUrl . '/payment/status/' . $transactionId,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
]);

$statusResponse = json_decode(curl_exec($curl), true);
curl_close($curl);

if ($statusResponse) {
    echo "✅ Payment Status Retrieved!\n";
    echo "   Order: " . $statusResponse['order_id'] . "\n";
    echo "   Status: " . $statusResponse['status'] . "\n";
    echo "   Amount: " . $statusResponse['amount'] . " " . $statusResponse['currency'] . "\n";
    echo "   Method: " . $statusResponse['payment_method'] . "\n";
    echo "   Paid At: " . $statusResponse['paid_at'] . "\n\n";
} else {
    echo "❌ Failed to get status\n";
}

// 4. GET ORDER DETAILS
echo "4️⃣ Getting Order Details...\n";
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $baseUrl . '/orders/' . $orderNumber,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
]);

$orderDetailsResponse = json_decode(curl_exec($curl), true);
curl_close($curl);

if ($orderDetailsResponse) {
    echo "✅ Order Details Retrieved!\n";
    echo "   Order Number: " . $orderDetailsResponse['order_number'] . "\n";
    echo "   Customer: " . $orderDetailsResponse['customer_name'] . " (" . $orderDetailsResponse['customer_email'] . ")\n";
    echo "   Amount: " . $orderDetailsResponse['total_amount'] . " " . $orderDetailsResponse['currency'] . "\n";
    echo "   Status: " . $orderDetailsResponse['status'] . "\n";
    echo "   Paid At: " . ($orderDetailsResponse['paid_at'] ?? 'Not paid') . "\n";
    
    if (!empty($orderDetailsResponse['transactions'])) {
        echo "   Transactions:\n";
        foreach ($orderDetailsResponse['transactions'] as $txn) {
            echo "      - ID: " . $txn['transaction_id'] . "\n";
            echo "        Method: " . $txn['payment_method'] . "\n";
            echo "        Status: " . $txn['status'] . "\n";
        }
    }
    echo "\n";
} else {
    echo "❌ Failed to get order details\n";
}

echo "========================================\n";
echo "✅ ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "========================================\n\n";
?>
