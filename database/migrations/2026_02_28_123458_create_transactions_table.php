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
            $table->foreignId('primary_person_id')->constrained('persons')->nullOnDelete();
            $table->foreignId('person_id')->constrained('persons')->nullOnDelete();
            $table->json('service_types')->nullable();
            $table->json('procedure_ids')->nullable();
            $table->json('diagnostics')->nullable();
            $table->string('prescription_img')->nullable();
            $table->string('pharmacy_charges')->nullable();
            $table->decimal('amount', 10, 2)->nullable();
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->enum('status', ['pending','completed','failed','refunded'])->default('pending');
            $table->integer('coins')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('invoice')->nullable();
            $table->boolean('is_notified')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->boolean('is_refunded')->default(false);
            $table->boolean('is_cancelled')->default(false);
            // $table->boolean('is_expired')->default(false);
            $table->boolean('is_failed')->default(false);
            $table->timestamps();
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
