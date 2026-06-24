<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->enum('appointment_status', [
                'new_scheduled',
                'checked_in',
                'completed',
                'cancelled',
            ])->default('new_scheduled')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropColumn('appointment_status');
        });
    }
};
