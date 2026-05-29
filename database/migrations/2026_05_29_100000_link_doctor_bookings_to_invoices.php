<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_booking_id')
                ->nullable()
                ->after('person_id');
            $table->foreign('doctor_booking_id')
                ->references('id')
                ->on('doctor_bookings')
                ->nullOnDelete();
        });

        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_id')
                ->nullable()
                ->after('is_online_payment');
            $table->string('payment_status', 20)
                ->default('unpaid')
                ->nullable()
                ->after('invoice_id');

            $table->foreign('invoice_id')
                ->references('id')
                ->on('invoices')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn(['invoice_id', 'payment_status']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['doctor_booking_id']);
            $table->dropColumn('doctor_booking_id');
        });
    }
};
