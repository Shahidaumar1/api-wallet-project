# Payment Gateway API - Integration Guide

یہ guide دوسری websites کو آپ کے Payment Gateway سے connect کرنے میں مدد دے گا۔

## Table of Contents
1. [API Credentials حاصل کریں](#credentials-حاصل-کریں)
2. [PHP Website Integration](#php-website-integration)
3. [Node.js/JavaScript Integration](#nodejs-integration)
4. [WordPress Integration](#wordpress-integration)
5. [Webhook Handling](#webhook-handling)
6. [API Responses](#api-responses)

---

## Credentials حاصل کریں

پہلے ہر نئی website کے لیے API credentials بنائیں:

```bash
php create_api_client.php
```

یہ آپ کو ملے گا:
```json
{
  "api_key": "pk_test_xxxxxxxxxx",
  "api_secret": "sk_test_yyyyyyyyyyyyy",
  "website_name": "My Website"
}
```

---

## PHP Website Integration

### 1. سادہ Order بنائیں

```php
<?php
$apiKey = "pk_test_xxxxxxxxxx";
$apiSecret = "sk_test_yyyyyyyyyyyyy";
$baseUrl = "http://127.0.0.1:8000/api";

// Order کی تفصیلات
$orderData = [
    "order_number" => "ORD-" . time(),
    "customer_name" => "Ali Ahmed",
    "customer_email" => "ali@example.com",
    "amount" => 5000, // PKR میں
    "currency" => "PKR",
    "description" => "Website کے لیے payment",
    "metadata" => [
        "user_id" => 123,
        "plan" => "premium"
    ]
];

// API call
$ch = curl_init($baseUrl . "/orders/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "api_key" => $apiKey,
    "api_secret" => $apiSecret,
    "order" => $orderData
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$responseData = json_decode($response, true);

if ($responseData['status'] === 'success') {
    $orderNumber = $responseData['data']['order']['order_number'];
    $transactionId = $responseData['data']['transaction']['id'];
    echo "Order بن گیا! Order Number: $orderNumber";
    // اب payment process کریں
} else {
    echo "Error: " . $responseData['message'];
}
curl_close($ch);
?>
```

### 2. Payment Process کریں

```php
<?php
// Payment کی تفصیلات
$paymentData = [
    "transaction_id" => "TXN-001", // یا order سے ملے ہوئے ID
    "amount" => 5000,
    "payment_method" => "stripe", // stripe, paypal, mobile_wallet, bank_transfer, card
    "description" => "Payment for order"
];

$ch = curl_init($baseUrl . "/payment/process");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "api_key" => $apiKey,
    "api_secret" => $apiSecret,
    "payment" => $paymentData
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$responseData = json_decode($response, true);

if ($responseData['status'] === 'success') {
    echo "Payment مکمل! Status: " . $responseData['data']['transaction']['status'];
} else {
    echo "Payment failed: " . $responseData['message'];
}
curl_close($ch);
?>
```

### 3. Payment Status چیک کریں

```php
<?php
$transactionId = "TXN-001";

$ch = curl_init($baseUrl . "/payment/status/$transactionId");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    "api_key" => $apiKey,
    "api_secret" => $apiSecret
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);

$response = curl_exec($ch);
$responseData = json_decode($response, true);

echo "Payment Status: " . $responseData['data']['transaction']['status'];
// Possible statuses: pending, processing, completed, failed, refunded
?>
```

---

## Node.js Integration

```javascript
const axios = require('axios');

const API_KEY = 'pk_test_xxxxxxxxxx';
const API_SECRET = 'sk_test_yyyyyyyyyyyyy';
const BASE_URL = 'http://127.0.0.1:8000/api';

// 1. Order بنائیں
async function createOrder() {
    try {
        const response = await axios.post(`${BASE_URL}/orders/create`, {
            api_key: API_KEY,
            api_secret: API_SECRET,
            order: {
                order_number: `ORD-${Date.now()}`,
                customer_name: 'Ahmed Khan',
                customer_email: 'ahmed@example.com',
                amount: 5000,
                currency: 'PKR',
                description: 'Premium subscription'
            }
        });
        
        console.log('Order Created:', response.data);
        return response.data;
    } catch (error) {
        console.error('Error creating order:', error.response?.data);
    }
}

// 2. Payment Process کریں
async function processPayment(transactionId) {
    try {
        const response = await axios.post(`${BASE_URL}/payment/process`, {
            api_key: API_KEY,
            api_secret: API_SECRET,
            payment: {
                transaction_id: transactionId,
                amount: 5000,
                payment_method: 'stripe',
                description: 'Premium subscription payment'
            }
        });
        
        console.log('Payment Processed:', response.data);
    } catch (error) {
        console.error('Error processing payment:', error.response?.data);
    }
}

// 3. Payment Status چیک کریں
async function checkPaymentStatus(transactionId) {
    try {
        const response = await axios.post(`${BASE_URL}/payment/status/${transactionId}`, {
            api_key: API_KEY,
            api_secret: API_SECRET
        });
        
        console.log('Payment Status:', response.data);
    } catch (error) {
        console.error('Error checking status:', error.response?.data);
    }
}

// استعمال کریں
(async () => {
    const orderResult = await createOrder();
    if (orderResult?.data?.transaction?.id) {
        await processPayment(orderResult.data.transaction.id);
    }
})();
```

---

## WordPress Integration

WordPress میں plugin کے ذریعے integrate کریں:

```php
<?php
// wp-content/plugins/payment-gateway/payment-gateway.php

function initiate_payment() {
    if (!isset($_POST['product_price'])) {
        return;
    }
    
    $price = $_POST['product_price'];
    $api_key = get_option('payment_gateway_api_key');
    $api_secret = get_option('payment_gateway_api_secret');
    
    $order_data = [
        'order_number' => 'WP-' . time(),
        'customer_name' => $_POST['customer_name'],
        'customer_email' => $_POST['customer_email'],
        'amount' => $price,
        'currency' => 'PKR'
    ];
    
    $response = wp_remote_post('http://127.0.0.1:8000/api/orders/create', [
        'method' => 'POST',
        'body' => json_encode([
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'order' => $order_data
        ]),
        'headers' => ['Content-Type' => 'application/json']
    ]);
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if ($body['status'] === 'success') {
        wp_redirect('payment-success.php?order=' . $body['data']['order']['order_number']);
    } else {
        wp_redirect('payment-error.php');
    }
}

add_action('wp_footer', 'initiate_payment');
?>
```

---

## Webhook Handling

جب payment مکمل ہو تو gateway آپ کی website کو webhook بھیجے گا۔

### Webhook Receiver بنائیں

```php
<?php
// webhook-receiver.php

$input = file_get_contents('php://input');
$webhook_data = json_decode($input, true);

// Webhook کی تصدیق کریں
if (isset($webhook_data['transaction_id'])) {
    $transaction_id = $webhook_data['transaction_id'];
    $status = $webhook_data['status'];
    $order_number = $webhook_data['order_number'];
    
    // Database میں update کریں
    $pdo = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'password');
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE order_number = ?');
    $stmt->execute([$status, $order_number]);
    
    // Email بھیجیں
    if ($status === 'completed') {
        send_payment_success_email($order_number);
    }
    
    // Response دیں
    http_response_code(200);
    echo json_encode(['status' => 'received']);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid webhook']);
}
?>
```

### Webhook کو Register کریں

```php
<?php
// اپنی website میں register کریں
$webhook_url = 'https://yourwebsite.com/webhook-receiver.php';

// یہ Gateway settings میں save کریں
$webhooks = [
    'payment_completed' => $webhook_url,
    'payment_failed' => $webhook_url,
    'refund_processed' => $webhook_url
];
?>
```

---

## API Responses

### Order Create Response

```json
{
  "status": "success",
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-1707572802",
      "customer_name": "Ali Ahmed",
      "customer_email": "ali@example.com",
      "amount": 5000,
      "currency": "PKR",
      "status": "pending",
      "created_at": "2026-02-10T10:00:00Z"
    },
    "transaction": {
      "id": "TXN-001",
      "status": "pending",
      "payment_method": null
    }
  }
}
```

### Payment Process Response

```json
{
  "status": "success",
  "data": {
    "transaction": {
      "id": "TXN-001",
      "order_id": 1,
      "amount": 5000,
      "payment_method": "stripe",
      "status": "completed",
      "created_at": "2026-02-10T10:00:00Z"
    }
  }
}
```

### Error Response

```json
{
  "status": "error",
  "message": "Invalid API credentials",
  "code": 401
}
```

---

## مثالیں چلانے کے لیے

### PHP Example
```bash
php examples/php-integration.php
```

### Node.js Example
```bash
node examples/nodejs-integration.js
```

---

## سوالات؟

Support: admin@api-wallet.local
Documentation: https://github.com/Shahidaumar1/api-wallet-project
