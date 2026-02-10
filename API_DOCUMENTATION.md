# Payment Gateway API Documentation

## خرح (Overview)

یہ API ایک مکمل **Payment Gateway** فراہم کرتا ہے جو:
- Orders بناتا ہے
- متعدد Payment Methods کو سپورٹ کرتا ہے
- Real-time Webhooks چلاتا ہے
- Transactions کو ٹریک کرتا ہے

---

## Features

### 1. **Payment Methods**
- Stripe
- PayPal
- Mobile Wallet (معاوضے)
- Bank Transfer
- Credit/Debit Card

### 2. **Multi-Website Integration**
- ہر website کو API Key ملتی ہے
- اپنے orders اور transactions الگ سے ٹریک کریں
- Webhooks کے ذریعے تنبیہ ملے

### 3. **WebHooks Support**
- Order create ہوتا ہے
- Payment success/failed ہوتی ہے
- Real-time notifications

---

## Database Schema

### 🔷 `api_clients` Table
```
- id (PK)
- name (website کا نام)
- api_key (منفرد)
- api_secret (منفرد)
- website_url
- webhook_url (callbacks کے لیے)
- allowed_ips
- is_active
- payment_methods (JSON array)
- contact_email
- contact_phone
- timestamps
```

### 🔷 `orders` Table
```
- id (PK)
- api_client_id (FK)
- order_number (منفرد)
- customer_email
- customer_name
- total_amount (decimal)
- currency (USD, PKR وغیرہ)
- status (pending, paid, failed, cancelled)
- description
- metadata (JSON)
- webhook_url
- paid_at
- timestamps
```

### 🔷 `transactions` Table
```
- id (PK)
- order_id (FK)
- api_client_id (FK)
- transaction_id (منفرد)
- amount
- currency
- payment_method (card, paypal, mobile_wallet, bank_transfer, stripe)
- status (pending, processing, success, failed, refunded)
- response_data (JSON)
- error_message
- paid_at
- timestamps
```

---

## API Endpoints

### 🟢 Public Endpoints (API Key Required)

#### 1. **Create Order**
```
POST /api/orders/create
Content-Type: application/json

{
  "api_key": "your_api_key",
  "customer_email": "customer@example.com",
  "customer_name": "John Doe",
  "total_amount": 100.00,
  "currency": "USD",
  "description": "Order Description",
  "metadata": {
    "custom_field": "value"
  },
  "webhook_url": "https://yoursite.com/webhook"
}

Response (201):
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order_id": 1,
    "order_number": "ORD_ABC1234_1707545678",
    "status": "pending",
    "amount": 100.00,
    "currency": "USD",
    "created_at": "2026-02-10T09:14:38Z"
  }
}
```

#### 2. **Process Payment**
```
POST /api/payment/process
Content-Type: application/json

{
  "api_key": "your_api_key",
  "order_id": 1,
  "payment_method": "stripe",
  "amount": 100.00,
  "currency": "USD",
  "card_token": "tok_xxx"  // جو payment method ہو اسی کے لیے
}

Response (200):
{
  "success": true,
  "message": "Payment processed successfully",
  "transaction_id": "TXN_uuid",
  "order_id": "ORD_ABC1234_1707545678"
}
```

#### 3. **Get Payment Status**
```
GET /api/payment/status/{transactionId}
Headers:
  X-API-Key: your_api_key

Response (200):
{
  "transaction_id": "TXN_uuid",
  "order_id": "ORD_ABC1234_1707545678",
  "status": "success",
  "amount": 100.00,
  "currency": "USD",
  "payment_method": "stripe",
  "paid_at": "2026-02-10T09:20:00Z",
  "created_at": "2026-02-10T09:14:38Z"
}
```

#### 4. **Get Order Details**
```
GET /api/orders/{orderNumber}
Headers:
  X-API-Key: your_api_key

Response (200):
{
  "order_id": 1,
  "order_number": "ORD_ABC1234_1707545678",
  "customer_name": "John Doe",
  "customer_email": "john@example.com",
  "total_amount": 100.00,
  "currency": "USD",
  "status": "paid",
  "paid_at": "2026-02-10T09:20:00Z",
  "transactions": [
    {
      "transaction_id": "TXN_uuid",
      "amount": 100.00,
      "payment_method": "stripe",
      "status": "success",
      "paid_at": "2026-02-10T09:20:00Z"
    }
  ]
}
```

### 🟡 WebHook Endpoints (Payment Gateways)

#### 1. **Stripe Webhook**
```
POST /api/webhooks/stripe
Content-Type: application/json
```

#### 2. **PayPal Webhook**
```
POST /api/webhooks/paypal
Content-Type: application/json
```

#### 3. **Mobile Wallet Webhook**
```
POST /api/webhooks/mobile-wallet
Content-Type: application/json
```

#### 4. **Bank Transfer Webhook**
```
POST /api/webhooks/bank
Content-Type: application/json
```

### 🔵 Authenticated Endpoints (require Sanctum Token)

#### 1. **Get Transactions**
```
GET /api/transactions
Headers:
  Authorization: Bearer {token}
  X-API-Key: your_api_key

Response: Paginated list
```

#### 2. **Get Orders**
```
GET /api/orders?status=paid
Headers:
  Authorization: Bearer {token}
  X-API-Key: your_api_key

Response: Paginated list
```

#### 3. **Refund Payment**
```
POST /api/refund
Headers:
  Authorization: Bearer {token}
  X-API-Key: your_api_key

{
  "transaction_id": "TXN_uuid"
}

Response (200):
{
  "success": true,
  "message": "Refund processed",
  "transaction_id": "TXN_uuid"
}
```

---

## WebHook Events

آپ کی website کو یہ notifications ملیں گے:

### پیمنٹ کامیاب
```json
{
  "event": "payment.success",
  "order": {
    "id": "ORD_ABC1234_1707545678",
    "amount": 100.00,
    "currency": "USD"
  },
  "transaction": {
    "id": "TXN_uuid",
    "method": "stripe",
    "status": "success"
  },
  "timestamp": "2026-02-10T09:20:00Z"
}
```

### پیمنٹ ناکام
```json
{
  "event": "payment.failed",
  "order": {
    "id": "ORD_ABC1234_1707545678",
    "amount": 100.00,
    "currency": "USD"
  },
  "error": "Card declined"
}
```

---

## Response Status Codes

| Code | Meaning |
|------|---------|
| 200 | کامیاب |
| 201 | بنایا گیا (Created) |
| 400 | غلط معلومات |
| 403 | اختیار نہیں (Unauthorized) |
| 404 | نہیں ملا |
| 500 | سرور میں خرابی |

---

## Error Responses

```json
{
  "error": "Payment method not allowed",
  "message": "The payment method is not configured for your account"
}
```

---

## Integration Example (PHP)

```php
<?php

$apiKey = 'your_api_key';
$baseUrl = 'http://api.wallet-project.local/api';

// 1. Create Order
$orderData = [
    'api_key' => $apiKey,
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'total_amount' => 100.00,
    'currency' => 'USD',
    'webhook_url' => 'https://yoursite.com/webhook'
];

$response = file_get_contents(
    $baseUrl . '/orders/create',
    false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($orderData)
        ]
    ])
);

$order = json_decode($response, true);
$orderId = $order['data']['order_id'];

// 2. Process Payment
$paymentData = [
    'api_key' => $apiKey,
    'order_id' => $orderId,
    'payment_method' => 'stripe',
    'amount' => 100.00,
    'currency' => 'USD',
    'card_token' => 'tok_xxx' // Stripe سے ملا ہوا
];

$response = file_get_contents(
    $baseUrl . '/payment/process',
    false,
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($paymentData)
        ]
    ])
);

$result = json_decode($response, true);

if ($result['success']) {
    echo "Payment successful: " . $result['transaction_id'];
} else {
    echo "Payment failed: " . $result['message'];
}
?>
```

---

## Setup/Testing

### 1. Database Setup
```bash
php artisan migrate
```

### 2. API Client بنائیں (CLI)
```bash
php artisan tinker

$client = \App\Models\ApiClient::create([
    'name' => 'My Website',
    'api_key' => 'sk_test_' . bin2hex(random_bytes(32)),
    'api_secret' => bin2hex(random_bytes(64)),
    'website_url' => 'https://mysite.com',
    'webhook_url' => 'https://mysite.com/webhook',
    'is_active' => true,
    'contact_email' => 'admin@mysite.com'
]);

echo $client->api_key;
```

### 3. Test کریں (API)
```bash
curl -X POST http://localhost/api/orders/create \
  -H "Content-Type: application/json" \
  -d '{
    "api_key": "your_api_key",
    "customer_email": "test@test.com",
    "customer_name": "Test User",
    "total_amount": 100,
    "currency": "USD"
  }'
```

---

## نکات

1. **API Key**: ہمیشہ محفوظ رکھیں - HTTPS استعمال کریں
2. **Webhook URL**: HTTPS ہونا لازمی ہے
3. **Payment Gateway**: اپنے Stripe/PayPal credentials شامل کریں
4. **Idempotency**: Same transaction دوبارہ نہ کریں - transaction_id چیک کریں
5. **Rate Limiting**: آنے والے اپڈیٹ میں شامل ہوں گی

---

## Support

مسائل یا سوالات کے لیے:
- Email: support@api-wallet.local
- Documentation: تمام endpoints اوپر دیے گئے ہیں

---

**Version**: 1.0.0  
**Last Updated**: 2026-02-10
