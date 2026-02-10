<?php
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'http://127.0.0.1:8000/api/orders/create',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'api_key' => 'sk_test_b9cb4be634dbecdeaf8eb031deb296b97978d294ae2b2696',
        'customer_email' => 'test@test.com',
        'customer_name' => 'Test User',
        'total_amount' => 500,
        'currency' => 'PKR'
    ]),
    CURLOPT_TIMEOUT => 10
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response:\n";
echo $response . "\n";

if ($error) {
    echo "Error: " . $error . "\n";
}
?>
