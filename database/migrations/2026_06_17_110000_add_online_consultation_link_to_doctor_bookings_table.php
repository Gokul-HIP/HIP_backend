<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->text('online_consultation_link')->nullable()->after('appointment_status');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropColumn('online_consultation_link');
        });
    }
};
