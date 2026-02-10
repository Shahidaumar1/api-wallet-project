<?php
/**
 * Webhook Receiver Example
 * 
 * جب Payment Gateway میں payment مکمل ہو تو وہ آپ کے webhook URL پر notification بھیجے گا
 * یہ فائل وہ webhook کو handle کرنے کی مثال ہے
 * 
 * اپنے Payment Gateway میں یہ URL register کریں:
 * https://yourwebsite.com/webhook-receiver.php
 * 
 * یا اگر local testing ہے تو:
 * ngrok یا localtunnel استعمال کریں webhook local URL expose کرنے کے لیے
 */

class WebhookProcessor {
    private $db;
    private $logFile = 'webhook-logs.txt';
    
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    /**
     * Webhook Process کریں
     */
    public function handle() {
        $input = file_get_contents('php://input');
        $webhookData = json_decode($input, true);
        
        // Log کریں
        $this->log('Webhook received: ' . json_encode($webhookData));
        
        // Validation
        if (!$this->validateWebhook($webhookData)) {
            $this->log('Invalid webhook data');
            http_response_code(400);
            return json_encode(['error' => 'Invalid webhook']);
        }
        
        // Process based on event type
        $eventType = $webhookData['event_type'] ?? null;
        
        switch ($eventType) {
            case 'payment.completed':
                return $this->handlePaymentCompleted($webhookData);
            case 'payment.failed':
                return $this->handlePaymentFailed($webhookData);
            case 'payment.pending':
                return $this->handlePaymentPending($webhookData);
            case 'refund.processed':
                return $this->handleRefundProcessed($webhookData);
            default:
                $this->log('Unknown event type: ' . $eventType);
                http_response_code(400);
                return json_encode(['error' => 'Unknown event type']);
        }
    }
    
    /**
     * جب payment مکمل ہو
     */
    private function handlePaymentCompleted($data) {
        $transactionId = $data['transaction_id'];
        $orderId = $data['order_id'];
        $orderNumber = $data['order_number'];
        $amount = $data['amount'];
        
        $this->log("✅ Payment completed: Order $orderNumber, Amount: $amount");
        
        // اپنا database update کریں
        if ($this->db) {
            $query = "UPDATE orders SET status = 'paid', payment_date = NOW() WHERE order_number = ?";
            // $this->db->execute($query, [$orderNumber]);
        }
        
        // Email بھیجیں - Payment Success
        $this->sendEmail(
            $data['customer_email'],
            "Payment Confirmation - Order $orderNumber",
            "یہاں آپ کی order مکمل ہو گئی ہے successfully۔"
        );
        
        // اپنے notification system میں شامل کریں
        $this->createNotification($orderNumber, "paid", "آپ کی payment مکمل ہو گئی!");
        
        // Webhook response
        http_response_code(200);
        return json_encode(['status' => 'processed']);
    }
    
    /**
     * جب payment ناکام ہو
     */
    private function handlePaymentFailed($data) {
        $orderNumber = $data['order_number'];
        $reason = $data['reason'] ?? 'Unknown';
        
        $this->log("❌ Payment failed: Order $orderNumber, Reason: $reason");
        
        // Database update
        if ($this->db) {
            $query = "UPDATE orders SET status = 'payment_failed', failure_reason = ? WHERE order_number = ?";
            // $this->db->execute($query, [$reason, $orderNumber]);
        }
        
        // Email بھیجیں - Payment Failed
        $this->sendEmail(
            $data['customer_email'],
            "Payment Failed - Order $orderNumber",
            "متافسانہ آپ کی payment ناکام رہی۔ براہ کرم دوبارہ کوشش کریں۔"
        );
        
        // Admin کو inform کریں
        $this->notifyAdmin("Payment failed for order: $orderNumber");
        
        http_response_code(200);
        return json_encode(['status' => 'processed']);
    }
    
    /**
     * جب payment pending ہو (Stripe, PayPal وغیرہ سے انتظار میں)
     */
    private function handlePaymentPending($data) {
        $orderNumber = $data['order_number'];
        
        $this->log("⏳ Payment pending: Order $orderNumber");
        
        if ($this->db) {
            $query = "UPDATE orders SET status = 'pending' WHERE order_number = ?";
            // $this->db->execute($query, [$orderNumber]);
        }
        
        http_response_code(200);
        return json_encode(['status' => 'processed']);
    }
    
    /**
     * جب refund process ہو
     */
    private function handleRefundProcessed($data) {
        $transactionId = $data['transaction_id'];
        $orderNumber = $data['order_number'];
        $refundAmount = $data['refund_amount'];
        
        $this->log("💰 Refund processed: Order $orderNumber, Amount: $refundAmount");
        
        if ($this->db) {
            $query = "UPDATE orders SET status = 'refunded', refund_date = NOW() WHERE order_number = ?";
            // $this->db->execute($query, [$orderNumber]);
        }
        
        // Email بھیجیں
        $this->sendEmail(
            $data['customer_email'],
            "Refund Confirmation - Order $orderNumber",
            "آپ کی refund کامیابی سے process ہو گئی ہے۔"
        );
        
        http_response_code(200);
        return json_encode(['status' => 'processed']);
    }
    
    /**
     * Webhook کی تصدیق کریں (اختیاری security)
     */
    private function validateWebhook($data) {
        // Check required fields
        $required = ['transaction_id', 'order_number', 'event_type'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // اگر signature موجود ہے تو verify کریں
        if (isset($data['signature'])) {
            $expectedSignature = hash_hmac('sha256', 
                json_encode($data), 
                'your-webhook-secret'
            );
            if ($data['signature'] !== $expectedSignature) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Email بھیجیں
     */
    private function sendEmail($to, $subject, $message) {
        $headers = "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: noreply@api-wallet.local\r\n";
        
        // Development میں صرف log کریں
        $this->log("Email would be sent to: $to, Subject: $subject");
        
        // Production میں uncomment کریں:
        // mail($to, $subject, $message, $headers);
    }
    
    /**
     * Admin کو متنبہ کریں
     */
    private function notifyAdmin($message) {
        $this->log("Admin notification: $message");
        
        // Slack یا دوسری notification service میں بھیجیں
        // $this->sendToSlack($message);
    }
    
    /**
     * Notification database میں save کریں
     */
    private function createNotification($orderNumber, $type, $message) {
        $this->log("Notification for $orderNumber ($type): $message");
        
        // Database میں save کریں
        if ($this->db) {
            // $this->db->insert('notifications', [
            //     'order_number' => $orderNumber,
            //     'type' => $type,
            //     'message' => $message,
            //     'created_at' => date('Y-m-d H:i:s')
            // ]);
        }
    }
    
    /**
     * Log میں record کریں
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message\n";
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
    }
}

// ============================================
// Webhook کو handle کریں
// ============================================

// اگر یہ webhook request ہے تو process کریں
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database connection (optional)
    // $db = new PDO('mysql:host=localhost;dbname=your_db', 'user', 'password');
    
    $processor = new WebhookProcessor(null);
    $response = $processor->handle();
    
    echo $response;
    exit;
}

// ============================================
// Development میں Webhook Tester
// ============================================

if ($_GET['test'] === 'true') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Webhook Tester</title>
        <style>
            body { font-family: Arial; max-width: 800px; margin: 20px auto; }
            .test-btn { padding: 10px 20px; margin: 10px 0; background: #007bff; color: white; border: 0; border-radius: 3px; cursor: pointer; }
            .test-btn:hover { background: #0056b3; }
            pre { background: #f5f5f5; padding: 10px; border-radius: 3px; overflow-x: auto; }
            .success { color: green; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>🧪 Webhook Tester</h2>
        
        <button class="test-btn" onclick="testWebhook('payment.completed')">
            ✅ Test: Payment Completed
        </button>
        
        <button class="test-btn" onclick="testWebhook('payment.failed')">
            ❌ Test: Payment Failed
        </button>
        
        <button class="test-btn" onclick="testWebhook('payment.pending')">
            ⏳ Test: Payment Pending
        </button>
        
        <button class="test-btn" onclick="testWebhook('refund.processed')">
            💰 Test: Refund Processed
        </button>
        
        <h3>📋 Log Output:</h3>
        <pre id="output">قدم دیکھنے کے لیے webhooks click کریں...</pre>
        
        <script>
        function testWebhook(eventType) {
            const payload = {
                event_type: eventType,
                transaction_id: 'TXN-' + Math.random().toString(36).substr(2, 9),
                order_id: Math.floor(Math.random() * 1000),
                order_number: 'ORD-' + Date.now(),
                amount: 5000,
                customer_email: 'test@example.com',
                reason: eventType === 'payment.failed' ? 'Insufficient funds' : null,
                refund_amount: eventType === 'refund.processed' ? 5000 : null
            };
            
            fetch('<?php echo $_SERVER['REQUEST_URI']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                document.getElementById('output').innerText = 
                    'Event: ' + eventType + '\n\n' +
                    'Payload:\n' + JSON.stringify(payload, null, 2) + '\n\n' +
                    'Response:\n' + JSON.stringify(data, null, 2);
            });
        }
        </script>
    </body>
    </html>
    <?php
}

?>
