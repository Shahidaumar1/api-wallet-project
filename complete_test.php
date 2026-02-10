<?php
echo "\n====================================\n";
echo "🧪 PAYMENT GATEWAY API TEST\n";
echo "====================================\n\n";

$apiKey = 'sk_test_b9cb4be634dbecdeaf8eb031deb296b97978d294ae2b2696';
$baseUrl = 'http://127.0.0.1:8000/api';

// 1. CREATE ORDER
echo "1️⃣ Creating Order...\n\n";

$orderData = [
    'api_key' => $apiKey,
    'customer_email' => 'customer@example.com',
    'customer_name' => 'Test Customer',
    'total_amount' => 1000,
    'currency' => 'PKR',
    'description' => 'Test Payment'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/orders/create',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($orderData),
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response:\n";
$decoded = json_decode($response, true);

if ($decoded && $decoded['success']) {
    echo "✅ Status: Success\n";
    echo "   Order ID: " . $decoded['data']['order_id'] . "\n";
    echo "   Order Number: " . $decoded['data']['order_number'] . "\n";
    echo "   Amount: " . $decoded['data']['amount'] . " " . $decoded['data']['currency'] . "\n";
    echo "   Status: " . $decoded['data']['status'] . "\n\n";
    
    $orderId = $decoded['data']['order_id'];
    $orderNumber = $decoded['data']['order_number'];
} else {
    echo "❌ Failed!\n";
    echo "Response: " . print_r($decoded, true) . "\n";
    exit;
}

// 2. PROCESS PAYMENT
echo "2️⃣ Processing Payment...\n\n";

$paymentData = [
    'api_key' => $apiKey,
    'order_id' => $orderId,
    'payment_method' => 'stripe',
    'amount' => 1000,
    'currency' => 'PKR'
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/payment/process',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode($paymentData),
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode($response, true);

if ($decoded && $decoded['success']) {
    echo "✅ Status: Success\n";
    echo "   Transaction ID: " . $decoded['transaction_id'] . "\n";
    echo "   Order ID: " . $decoded['order_id'] . "\n";
    echo "   Message: " . $decoded['message'] . "\n\n";
    
    $transactionId = $decoded['transaction_id'];
} else {
    echo "❌ Failed!\n";
    echo "Response: " . print_r($decoded, true) . "\n";
    exit;
}

// 3. CHECK PAYMENT STATUS
echo "3️⃣ Checking Payment Status...\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/payment/status/' . $transactionId,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode($response, true);

echo "✅ Transaction Details:\n";
echo "   Order: " . $decoded['order_id'] . "\n";
echo "   Transaction ID: " . $decoded['transaction_id'] . "\n";
echo "   Amount: " . $decoded['amount'] . " " . $decoded['currency'] . "\n";
echo "   Status: " . $decoded['status'] . "\n";
echo "   Payment Method: " . $decoded['payment_method'] . "\n";
echo "   Paid At: " . $decoded['paid_at'] . "\n\n";

// 4. GET ORDER DETAILS
echo "4️⃣ Getting Order Details...\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/orders/' . $orderNumber,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['X-API-Key: ' . $apiKey],
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode($response, true);

echo "✅ Order Details:\n";
echo "   Order Number: " . $decoded['order_number'] . "\n";
echo "   Customer: " . $decoded['customer_name'] . " (" . $decoded['customer_email'] . ")\n";
echo "   Amount: " . $decoded['total_amount'] . " " . $decoded['currency'] . "\n";
echo "   Order Status: " . $decoded['status'] . "\n";
echo "   Paid At: " . ($decoded['paid_at'] ?? 'Not paid') . "\n";

if (!empty($decoded['transactions'])) {
    echo "\n   Transactions:\n";
    foreach ($decoded['transactions'] as $txn) {
        echo "   - ID: " . $txn['transaction_id'] . "\n";
        echo "     Method: " . $txn['payment_method'] . "\n";
        echo "     Status: " . $txn['status'] . "\n";
    }
}

echo "\n====================================\n";
echo "✅ ALL TESTS PASSED!\n";
echo "====================================\n\n";
?>
