<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('doctor_bookings') || ! Schema::hasColumn('doctor_bookings', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE doctor_bookings MODIFY status ENUM('pending','confirmed','cancelled','completed','missed') NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (! Schema::hasTable('doctor_bookings') || ! Schema::hasColumn('doctor_bookings', 'status')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE doctor_bookings MODIFY status ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending'");
    }
};
