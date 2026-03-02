<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mattiverse\Userstamps\Traits\Userstamps;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('primary_person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->foreignId('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->json('service_types')->nullable();
            $table->json('invoice_details')->nullable();
            $table->string('prescription_img')->nullable();
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('total_gst', 10, 2)->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('service_charges', 10, 2)->nullable();
            $table->decimal('payment_gateway_charges', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->enum('status', ['pending','completed','failed','refunded','cancelled'])->default('pending');
            $table->integer('coins_applied')->nullable();
            $table->integer('coins_earned')->nullable();
            $table->string('payment_method')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
