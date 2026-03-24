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
        Schema::create('razorpay_payments', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->foreignUuid('invoice_id')
                  ->constrained('invoices')
                  ->cascadeOnDelete();

            $table->string('razorpay_order_id')->nullable();
            $table->string('razorpay_payment_id')->nullable();
            $table->string('razorpay_signature')->nullable();

            $table->string('payment_method')->nullable();
            $table->string('bank')->nullable();
            $table->string('wallet')->nullable();
            $table->string('vpa')->nullable();

            $table->string('card_last4')->nullable();
            $table->string('card_network')->nullable();

            $table->decimal('amount_paid', 10, 2);
            $table->decimal('razorpay_fee', 10, 2)->nullable();
            $table->decimal('razorpay_tax', 10, 2)->nullable();

            $table->string('currency')->default('INR');
            $table->string('payment_status');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('razorpay_payments');
    }
};