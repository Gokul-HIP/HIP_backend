<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hms_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_id')->constrained('hms_integrations')->cascadeOnDelete();
            $table->foreignId('hms_booking_row_id')->constrained('hms_bookings')->cascadeOnDelete();
            $table->string('payment_reference');
            $table->string('status', 32);
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('result', 64);
            $table->json('payload');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['integration_id', 'payment_reference'], 'hms_payments_integration_reference_unique');
            $table->index('hms_booking_row_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hms_payments');
    }
};
