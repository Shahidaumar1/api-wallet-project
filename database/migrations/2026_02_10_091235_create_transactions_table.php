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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('api_client_id')->constrained()->onDelete('cascade');
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('payment_method', ['card', 'paypal', 'mobile_wallet', 'bank_transfer', 'stripe']);
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'refunded'])->default('pending');
            $table->text('response_data')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('api_client_id');
            $table->index('status');
            $table->index('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
