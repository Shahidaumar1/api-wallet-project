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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_client_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('customer_email');
            $table->string('customer_name');
            $table->decimal('total_amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('webhook_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index('api_client_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
