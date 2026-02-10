<?php
require 'vendor/autoload.php';

use App\Models\ApiClient;

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Initialize Laravel app
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create API Client
$client = ApiClient::create([
    'name' => 'Test Store',
    'api_key' => 'sk_test_' . bin2hex(random_bytes(24)),
    'api_secret' => bin2hex(random_bytes(32)),
    'website_url' => 'https://teststore.com',
    'webhook_url' => 'https://teststore.com/api/webhook',
    'is_active' => true,
    'payment_methods' => json_encode(['stripe', 'paypal', 'card', 'mobile_wallet', 'bank_transfer']),
    'contact_email' => 'admin@teststore.com',
    'contact_phone' => '+92123456789'
]);

echo "\n";
echo "=================================\n";
echo "✅ API CLIENT CREATED SUCCESSFULLY\n";
echo "=================================\n\n";
echo "📌 Client ID: " . $client->id . "\n";
echo "📌 Name: " . $client->name . "\n";
echo "📌 API Key:\n   " . $client->api_key . "\n";
echo "📌 API Secret:\n   " . $client->api_secret . "\n";
echo "📌 website URL: " . $client->website_url . "\n";
echo "📌 Webhook URL: " . $client->webhook_url . "\n";
echo "📌 Payment Methods: " . implode(', ', json_decode($client->payment_methods)) . "\n";
echo "📌 Active: " . ($client->is_active ? 'Yes' : 'No') . "\n";
echo "📌 Created At: " . $client->created_at . "\n";
echo "\n=================================\n";
echo "💾 اب یہ API Key استعمال کریں!\n";
echo "=================================\n\n";
?>
