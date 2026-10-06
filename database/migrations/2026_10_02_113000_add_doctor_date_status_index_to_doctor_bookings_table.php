<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->index(
                ['doctor_id', 'booking_date', 'appointment_status'],
                'doctor_bookings_doctor_date_appt_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropIndex('doctor_bookings_doctor_date_appt_status_index');
        });
    }
};
