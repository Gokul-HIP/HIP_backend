<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a disease-chatbot session to at most one doctor booking for payment tracking.
 *
 * doctor_booking_id matches doctor_bookings.id (bigint).
 * Do not run on production from this task — migrations are written only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_chatbot_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')
                ->constrained('disease_chatbot_sessions')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('doctor_booking_id');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status'); // pending_payment|confirmed|expired|cancelled|refund_required
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique('session_id');
            $table->unique('doctor_booking_id');
            $table->foreign('doctor_booking_id')
                ->references('id')
                ->on('doctor_bookings')
                ->cascadeOnDelete();
            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_chatbot_bookings');
    }
};
