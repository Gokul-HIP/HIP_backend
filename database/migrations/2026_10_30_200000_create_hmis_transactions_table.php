<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmis_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->string('hospital_code')->nullable();
            $table->string('mrn_id');
            $table->string('person_id')->nullable();
            $table->string('user_id')->nullable();
            $table->string('payment_type');
            $table->timestamp('paid_at');
            $table->string('time_slot', 50)->nullable();
            $table->boolean('is_paid')->nullable();
            $table->string('status');
            $table->string('doctor_id')->nullable();
            $table->string('doctor_name')->nullable();
            $table->string('lab_test_name')->nullable();
            $table->decimal('original_amount', 12, 2);
            $table->boolean('is_membership_applied')->default(false);
            $table->unsignedBigInteger('membership_id')->nullable();
            $table->decimal('percentage_discount_applied', 5, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->boolean('is_coins_used')->default(false);
            $table->unsignedInteger('coins_used')->default(0);
            $table->decimal('coin_discount_amount', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2);
            $table->unsignedInteger('coins_earned')->default(0);
            $table->unsignedInteger('coins_reversed')->default(0);
            $table->boolean('is_refund')->default(false);
            $table->string('refund_of_transaction_id')->nullable();
            $table->json('flags');
            $table->json('other_data')->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->index('mrn_id');
            $table->index('person_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmis_transactions');
    }
};
