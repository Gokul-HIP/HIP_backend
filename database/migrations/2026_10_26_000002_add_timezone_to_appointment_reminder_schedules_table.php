<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appointment_reminder_schedules') && ! Schema::hasColumn('appointment_reminder_schedules', 'timezone')) {
            Schema::table('appointment_reminder_schedules', function (Blueprint $table) {
                $table->string('timezone', 64)->default('UTC')->after('appointment_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appointment_reminder_schedules') && Schema::hasColumn('appointment_reminder_schedules', 'timezone')) {
            Schema::table('appointment_reminder_schedules', function (Blueprint $table) {
                $table->dropColumn('timezone');
            });
        }
    }
};
