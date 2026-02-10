<?php
/**
 * Payment Gateway Integration Example - PHP Website
 * 
 * یہ فائل دکھاتی ہے کہ کسی دوسری PHP website سے 
 * Payment Gateway API کو کیسے استعمال کریں
 * 
 * استعمال: 
 * - اپنی API credentials دریافت کریں
 * - اس فائل میں credentials لگائیں
 * - پھر اسے اپنی website میں integrate کریں
 */

class PaymentGatewayClient {
    private $apiKey;
    private $apiSecret;
    private $baseUrl;
    
    public function __construct($apiKey, $apiSecret, $baseUrl = 'http://127.0.0.1:8000/api') {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * Order بنائیں
     */
    public function createOrder($orderData) {
        $params = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'order' => $orderData
        ];
        
        return $this->makeRequest('POST', '/orders/create', $params);
    }
    
    /**
     * Payment Process کریں
     */
    public function processPayment($paymentData) {
        $params = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'payment' => $paymentData
        ];
        
        return $this->makeRequest('POST', '/payment/process', $params);
    }
    
    /**
     * Payment Status چیک کریں
     */
    public function checkPaymentStatus($transactionId) {
        $params = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret
        ];
        
        return $this->makeRequest('POST', "/payment/status/$transactionId", $params);
    }
    
    /**
     * Refund Process کریں
     */
    public function refundPayment($transactionId, $amount = null) {
        $params = [
            'api_key' => $this->apiKey,
            'api_secret' => $this->apiSecret,
            'transaction_id' => $transactionId
        ];
        
        if ($amount) {
            $params['amount'] = $amount;
        }
        
        return $this->makeRequest('POST', '/payment/refund', $params);
    }
    
    /**
     * HTTP Request بھیجیں
     */
    private function makeRequest($method, $endpoint, $data) {
        $url = $this->baseUrl . $endpoint;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'status' => 'error',
                'message' => $error,
                'code' => null
            ];
        }
        
        $decoded = json_decode($response, true);
        $decoded['http_code'] = $httpCode;
        
        return $decoded;
    }
}

// ============================================
// استعمال کی مثال
// ============================================

if (php_sapi_name() === 'cli') {
    // Command line میں چلانے کے لیے
    
    // 1. API Credentials
    $apiKey = 'pk_test_1707572802';      // اپنی API Key ڈالیں
    $apiSecret = 'sk_test_secret';        // اپنی API Secret ڈالیں
    
    // 2. Client بنائیں
    $client = new PaymentGatewayClient($apiKey, $apiSecret);
    
    echo "=== Payment Gateway Integration Test ===\n\n";
    
    // 3. Order بنائیں
    echo "1. Creating Order...\n";
    $orderResponse = $client->createOrder([
        'order_number' => 'TEST-' . time(),
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'amount' => 5000,
        'currency' => 'PKR',
        'description' => 'Test order from integration example'
    ]);
    
    echo "Response: " . json_encode($orderResponse, JSON_PRETTY_PRINT) . "\n\n";
    
    if ($orderResponse['status'] === 'success') {
        $transactionId = $orderResponse['data']['transaction']['id'];
        $orderNumber = $orderResponse['data']['order']['order_number'];
        
        // 4. Payment Process کریں
        echo "2. Processing Payment...\n";
        $paymentResponse = $client->processPayment([
            'transaction_id' => $transactionId,
            'amount' => 5000,
            'payment_method' => 'stripe',
            'description' => 'Payment for test order'
        ]);
        
        echo "Response: " . json_encode($paymentResponse, JSON_PRETTY_PRINT) . "\n\n";
        
        // 5. Payment Status چیک کریں
        echo "3. Checking Payment Status...\n";
        $statusResponse = $client->checkPaymentStatus($transactionId);
        echo "Response: " . json_encode($statusResponse, JSON_PRETTY_PRINT) . "\n\n";
        
        // 6. Refund کریں
        echo "4. Processing Refund (optional)...\n";
        $refundResponse = $client->refundPayment($transactionId, 5000);
        echo "Response: " . json_encode($refundResponse, JSON_PRETTY_PRINT) . "\n\n";
    } else {
        echo "Order creation failed: " . $orderResponse['message'] . "\n";
    }
}

?>
