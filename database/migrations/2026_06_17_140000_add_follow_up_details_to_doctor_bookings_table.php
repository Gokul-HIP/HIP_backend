<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->string('follow_up_reason')->nullable()->after('reason_of_visit');
            $table->text('clinical_notes')->nullable()->after('follow_up_reason');
            $table->boolean('send_notification_reminder')->default(false)->after('clinical_notes');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'follow_up_reason',
                'clinical_notes',
                'send_notification_reminder',
            ]);
        });
    }
};
