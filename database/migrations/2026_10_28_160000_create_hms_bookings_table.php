<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_bookings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('hms_integrations')->cascadeOnDelete();
            $table->string('hms_booking_id');
            $table->string('booking_type', 32);
            $table->unsignedBigInteger('booking_id');
            $table->uuid('hip_user_id');
            $table->uuid('person_id');
            $table->unsignedBigInteger('hospital_id');
            $table->string('status', 32);
            $table->decimal('expected_amount', 12, 2);
            $table->decimal('hms_payable_amount', 12, 2);
            $table->boolean('price_mismatch')->default(false);
            $table->json('pricing_snapshot');
            $table->char('request_hash', 64);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['integration_id', 'hms_booking_id'], 'hms_bookings_integration_booking_unique');
            $table->index(['status', 'hold_expires_at'], 'hms_bookings_hold_idx');
            $table->index(['booking_type', 'booking_id'], 'hms_bookings_local_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_bookings');
    }
};
