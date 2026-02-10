/**
 * Payment Gateway Integration Example - Node.js / JavaScript
 * 
 * یہ فائل دکھاتی ہے کہ کسی دوسری Node.js/JavaScript website سے 
 * Payment Gateway API کو کیسے استعمال کریں
 * 
 * Installation:
 * npm install axios
 * 
 * استعمال:
 * node examples/nodejs-website-integration.js
 */

const axios = require('axios');

class PaymentGatewayClient {
    constructor(apiKey, apiSecret, baseUrl = 'http://127.0.0.1:8000/api') {
        this.apiKey = apiKey;
        this.apiSecret = apiSecret;
        this.baseUrl = baseUrl;
    }
    
    /**
     * Order بنائیں
     */
    async createOrder(orderData) {
        try {
            const response = await axios.post(`${this.baseUrl}/orders/create`, {
                api_key: this.apiKey,
                api_secret: this.apiSecret,
                order: orderData
            });
            return response.data;
        } catch (error) {
            return {
                status: 'error',
                message: error.response?.data?.message || error.message,
                code: error.response?.status
            };
        }
    }
    
    /**
     * Payment Process کریں
     */
    async processPayment(paymentData) {
        try {
            const response = await axios.post(`${this.baseUrl}/payment/process`, {
                api_key: this.apiKey,
                api_secret: this.apiSecret,
                payment: paymentData
            });
            return response.data;
        } catch (error) {
            return {
                status: 'error',
                message: error.response?.data?.message || error.message,
                code: error.response?.status
            };
        }
    }
    
    /**
     * Payment Status چیک کریں
     */
    async checkPaymentStatus(transactionId) {
        try {
            const response = await axios.post(`${this.baseUrl}/payment/status/${transactionId}`, {
                api_key: this.apiKey,
                api_secret: this.apiSecret
            });
            return response.data;
        } catch (error) {
            return {
                status: 'error',
                message: error.response?.data?.message || error.message,
                code: error.response?.status
            };
        }
    }
    
    /**
     * Refund Process کریں
     */
    async refundPayment(transactionId, amount = null) {
        try {
            const data = {
                api_key: this.apiKey,
                api_secret: this.apiSecret,
                transaction_id: transactionId
            };
            
            if (amount) {
                data.amount = amount;
            }
            
            const response = await axios.post(`${this.baseUrl}/payment/refund`, data);
            return response.data;
        } catch (error) {
            return {
                status: 'error',
                message: error.response?.data?.message || error.message,
                code: error.response?.status
            };
        }
    }
}

// ============================================
// مثال - Express.js میں استعمال
// ============================================

// npm install express body-parser

const express = require('express');
const bodyParser = require('body-parser');

const app = express();
app.use(bodyParser.json());

// API Credentials
const API_KEY = 'pk_test_1707572802';      // اپنی API Key ڈالیں
const API_SECRET = 'sk_test_secret';        // اپنی API Secret ڈالیں

const paymentClient = new PaymentGatewayClient(API_KEY, API_SECRET);

// Payment صفحہ
app.get('/checkout', (req, res) => {
    res.send(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payment Checkout</title>
            <style>
                body { font-family: Arial; max-width: 500px; margin: 50px auto; }
                form { background: #f5f5f5; padding: 20px; border-radius: 5px; }
                input { width: 100%; padding: 8px; margin: 10px 0; box-sizing: border-box; }
                button { background: #007bff; color: white; padding: 10px 20px; border: 0; border-radius: 3px; cursor: pointer; width: 100%; }
                button:hover { background: #0056b3; }
            </style>
        </head>
        <body>
            <h2>Payment Checkout</h2>
            <form action="/process-payment" method="POST">
                <input type="text" name="customerName" placeholder="نام" required>
                <input type="email" name="customerEmail" placeholder="ای میل" required>
                <input type="number" name="amount" placeholder="رقم (PKR)" value="5000" required>
                <button type="submit">Payment کریں</button>
            </form>
        </body>
        </html>
    `);
});

// Payment Process
app.post('/process-payment', async (req, res) => {
    try {
        const { customerName, customerEmail, amount } = req.body;
        
        // 1. Order بنائیں
        const orderResponse = await paymentClient.createOrder({
            order_number: `ORDER-${Date.now()}`,
            customer_name: customerName,
            customer_email: customerEmail,
            amount: parseInt(amount),
            currency: 'PKR',
            description: 'Website payment'
        });
        
        if (orderResponse.status !== 'success') {
            return res.status(400).json(orderResponse);
        }
        
        const transactionId = orderResponse.data.transaction.id;
        const orderNumber = orderResponse.data.order.order_number;
        
        // 2. Payment Process کریں
        const paymentResponse = await paymentClient.processPayment({
            transaction_id: transactionId,
            amount: parseInt(amount),
            payment_method: 'stripe',
            description: `Payment for ${orderNumber}`
        });
        
        if (paymentResponse.status === 'success') {
            res.send(`
                <h2>✅ Payment کامیاب!</h2>
                <p>Order Number: ${orderNumber}</p>
                <p>Transaction ID: ${transactionId}</p>
                <p>Status: ${paymentResponse.data.transaction.status}</p>
                <p><a href="/checkout">واپس جائیں</a></p>
            `);
        } else {
            res.status(400).send(`<h2>❌ Payment ناکام</h2><p>${paymentResponse.message}</p>`);
        }
        
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Webhook Receiver
app.post('/webhook', (req, res) => {
    const { transaction_id, status, order_number } = req.body;
    
    console.log(`📧 Webhook received: Order ${order_number}, Status: ${status}`);
    
    // اپنے database میں update کریں
    // db.updateOrderStatus(order_number, status);
    
    // Email یا notification بھیجیں
    // sendEmail(status === 'completed' ? 'success' : 'failed');
    
    res.json({ status: 'received' });
});

// Export for testing
module.exports = { PaymentGatewayClient, app };

// ============================================
// CLI میں ٹیسٹ کریں
// ============================================

if (require.main === module) {
    (async () => {
        console.log('=== Payment Gateway Integration Test ===\n');
        
        const client = new PaymentGatewayClient(API_KEY, API_SECRET);
        
        try {
            // 1. Order بنائیں
            console.log('1️⃣ Creating Order...');
            const orderResponse = await client.createOrder({
                order_number: `TEST-${Date.now()}`,
                customer_name: 'Test User',
                customer_email: 'test@example.com',
                amount: 5000,
                currency: 'PKR',
                description: 'Test order from Node.js'
            });
            
            console.log('✅ Response:', JSON.stringify(orderResponse, null, 2), '\n');
            
            if (orderResponse.status === 'success') {
                const transactionId = orderResponse.data.transaction.id;
                const orderNumber = orderResponse.data.order.order_number;
                
                // 2. Payment Process کریں
                console.log('2️⃣ Processing Payment...');
                const paymentResponse = await client.processPayment({
                    transaction_id: transactionId,
                    amount: 5000,
                    payment_method: 'stripe',
                    description: 'Payment for test order'
                });
                
                console.log('✅ Response:', JSON.stringify(paymentResponse, null, 2), '\n');
                
                // 3. Status چیک کریں
                console.log('3️⃣ Checking Payment Status...');
                const statusResponse = await client.checkPaymentStatus(transactionId);
                console.log('✅ Response:', JSON.stringify(statusResponse, null, 2), '\n');
                
                // 4. Refund کریں
                console.log('4️⃣ Processing Refund...');
                const refundResponse = await client.refundPayment(transactionId, 5000);
                console.log('✅ Response:', JSON.stringify(refundResponse, null, 2), '\n');
            }
            
        } catch (error) {
            console.error('❌ Error:', error.message);
        }
        
        // Server شروع کریں (optional)
        // const PORT = 3000;
        // app.listen(PORT, () => {
        //     console.log(`🚀 Server running on http://localhost:${PORT}`);
        //     console.log(`📝 Checkout: http://localhost:${PORT}/checkout`);
        // });
    })();
}
