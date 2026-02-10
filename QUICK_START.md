# Quick Start Guide - Payment Gateway API

## Installation

### 1. Database Setup
```bash
php artisan migrate
```

### 2. Create API Client
```bash
php artisan tinker
```

```php
use App\Models\ApiClient;

$client = ApiClient::create([
    'name' => 'My Store',
    'api_key' => 'sk_test_' . bin2hex(random_bytes(32)),
    'api_secret' => bin2hex(random_bytes(64)),
    'website_url' => 'https://mystore.com',
    'webhook_url' => 'https://mystore.com/api/webhook',
    'is_active' => true,
    'payment_methods' => ['stripe', 'paypal', 'card', 'mobile_wallet', 'bank_transfer'],
    'contact_email' => 'admin@mystore.com'
]);

echo "API Key: " . $client->api_key;
```

---

## Testing

### Best Method - Use Postman or Insomnia

#### 1️⃣ Create Order
```
POST http://localhost:8000/api/orders/create

{
  "api_key": "your_api_key",
  "customer_email": "john@example.com",
  "customer_name": "John Doe",
  "total_amount": 500,
  "currency": "PKR",
  "description": "Test Order",
  "webhook_url": "https://yoursite.com/webhook"
}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "order_id": 1,
    "order_number": "ORD_ABCD1234_1707545678"
  }
}
```

---

#### 2️⃣ Process Payment
```
POST http://localhost:8000/api/payment/process

{
  "api_key": "your_api_key",
  "order_id": 1,
  "payment_method": "stripe",
  "amount": 500,
  "currency": "PKR",
  "card_token": "tok_visa"
}
```

**Response:**
```json
{
  "success": true,
  "transaction_id": "TXN_550e8400-e29b-41d4-a716-446655440000",
  "order_id": "ORD_ABCD1234_1707545678"
}
```

---

#### 3️⃣ Check Payment Status
```
GET http://localhost:8000/api/payment/status/TXN_550e8400-e29b-41d4-a716-446655440000

Headers:
  X-API-Key: your_api_key
```

**Response:**
```json
{
  "transaction_id": "TXN_550e8400-e29b-41d4-a716-446655440000",
  "order_id": "ORD_ABCD1234_1707545678",
  "status": "success",
  "amount": 500,
  "currency": "PKR",
  "payment_method": "stripe",
  "paid_at": "2026-02-10T10:30:00Z"
}
```

---

## Code Integration

### JavaScript/Node.js
```javascript
const apiKey = 'your_api_key';
const baseUrl = 'http://localhost:8000/api';

async function createOrder(customerEmail, amount) {
  const response = await fetch(`${baseUrl}/orders/create`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      api_key: apiKey,
      customer_email: customerEmail,
      customer_name: 'Customer Name',
      total_amount: amount,
      currency: 'PKR',
      webhook_url: 'https://yoursite.com/webhook'
    })
  });
  
  return response.json();
}

async function processPayment(orderId, amount, paymentMethod) {
  const response = await fetch(`${baseUrl}/payment/process`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      api_key: apiKey,
      order_id: orderId,
      payment_method: paymentMethod,
      amount: amount,
      currency: 'PKR',
      card_token: 'tok_visa' // Stripe سے ملا
    })
  });
  
  return response.json();
}

// استعمال
const order = await createOrder('john@example.com', 500);
console.log('Order ID:', order.data.order_id);

const payment = await processPayment(order.data.order_id, 500, 'stripe');
console.log('Transaction ID:', payment.transaction_id);
```

---

### Python
```python
import requests
import json

API_KEY = 'your_api_key'
BASE_URL = 'http://localhost:8000/api'

def create_order(email, amount):
    payload = {
        'api_key': API_KEY,
        'customer_email': email,
        'customer_name': 'Customer',
        'total_amount': amount,
        'currency': 'PKR',
        'webhook_url': 'https://yoursite.com/webhook'
    }
    
    response = requests.post(f'{BASE_URL}/orders/create', json=payload)
    return response.json()

def process_payment(order_id, amount, method):
    payload = {
        'api_key': API_KEY,
        'order_id': order_id,
        'payment_method': method,
        'amount': amount,
        'currency': 'PKR'
    }
    
    response = requests.post(f'{BASE_URL}/payment/process', json=payload)
    return response.json()

# استعمال
order = create_order('john@example.com', 500)
print(f"Order ID: {order['data']['order_id']}")

payment = process_payment(order['data']['order_id'], 500, 'stripe')
print(f"Transaction: {payment['transaction_id']}")
```

---

### PHP
```php
<?php
class PaymentGateway {
    private $apiKey;
    private $baseUrl;
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
        $this->baseUrl = 'http://localhost:8000/api';
    }
    
    public function createOrder($email, $name, $amount, $currency = 'PKR') {
        $payload = [
            'api_key' => $this->apiKey,
            'customer_email' => $email,
            'customer_name' => $name,
            'total_amount' => $amount,
            'currency' => $currency,
            'webhook_url' => 'https://yoursite.com/webhook'
        ];
        
        return $this->request('POST', '/orders/create', $payload);
    }
    
    public function processPayment($orderId, $amount, $method, $currency = 'PKR') {
        $payload = [
            'api_key' => $this->apiKey,
            'order_id' => $orderId,
            'payment_method' => $method,
            'amount' => $amount,
            'currency' => $currency
        ];
        
        return $this->request('POST', '/payment/process', $payload);
    }
    
    private function request($method, $endpoint, $payload) {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => 'Content-Type: application/json',
                'content' => json_encode($payload)
            ]
        ]);
        
        $response = file_get_contents(
            $this->baseUrl . $endpoint,
            false,
            $context
        );
        
        return json_decode($response, true);
    }
}

// استعمال
$gateway = new PaymentGateway('your_api_key');

$order = $gateway->createOrder('john@example.com', 'John Doe', 500, 'PKR');
echo "Order ID: " . $order['data']['order_id'];

$payment = $gateway->processPayment($order['data']['order_id'], 500, 'stripe', 'PKR');
echo "Transaction: " . $payment['transaction_id'];
?>
```

---

## Webhook Handling

### آپ کی website پر webhook receiver بنائیں:

#### PHP
```php
<?php
header('Content-Type: application/json');

$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Log payment
error_log('Payment Event: ' . $data['event']);
error_log('Order: ' . $data['order']['id']);
error_log('Status: ' . $data['transaction']['status']);

if ($data['event'] === 'payment.success') {
    // Database میں order status update کریں
    // Customer کو email بھیجیں
    // Product deliver کریں
    
    updateOrderStatus($data['order']['id'], 'paid');
    sendConfirmationEmail($data['order']['id']);
}

http_response_code(200);
echo json_encode(['received' => true]);
?>
```

#### JavaScript/Node.js
```javascript
app.post('/webhook', (req, res) => {
    const { event, order, transaction } = req.body;
    
    console.log(`Payment Event: ${event}`);
    console.log(`Order: ${order.id}`);
    console.log(`Status: ${transaction.status}`);
    
    if (event === 'payment.success') {
        // Order کو complete کریں
        updateOrderStatus(order.id, 'paid');
        sendConfirmationEmail(order.id);
    }
    
    res.json({ received: true });
});
```

---

## Payment Methods Testing

### Stripe
```
Card: 4242 4242 4242 4242
Expiry: 12/25
CVC: 123
```

### PayPal
- Sandbox account استعمال کریں

### Mobile Wallet
- Testing configurations آنے والے ہیں

---

## مسائل کا حل

### 1. API Key غلط ہے
```
Error: API client not found
Solution: پہلے آپ کی API client بنائیں (tinker کے ذریعے)
```

### 2. Order نہیں مل رہی
```
Error: Order not found
Solution: صحیح API Key اور Order ID چیک کریں
```

### 3. Payment fail ہو رہی ہے
```
Error: Payment processing failed
Solution: Payment gateway credentials add کریں
```

---

## اگلے قدم

1. ✅ Database migrations چلائیں
2. ✅ API Client بنائیں
3. ✅ Testing کریں Postman میں
4. ✅ اپنی website میں integrate کریں
5. ⏳ Production میں deploy کریں

---

**More Help**: API_DOCUMENTATION.md کو دیکھیں
