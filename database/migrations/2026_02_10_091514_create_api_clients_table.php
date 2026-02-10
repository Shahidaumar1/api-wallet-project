<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_key')->unique();
            $table->string('api_secret')->unique();
            $table->string('website_url');
            $table->string('webhook_url')->nullable();
            $table->text('allowed_ips')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('payment_methods')->default(json_encode(['card', 'paypal', 'mobile_wallet', 'bank_transfer']));
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->timestamps();
            $table->index('api_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_clients');
    }
};
