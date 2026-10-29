<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hms_bookings', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('confirmed_at');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
        });

        Schema::table('hms_memberships', function (Blueprint $table): void {
            $table->timestamp('cancelled_at')->nullable()->after('request_hash');
        });
    }

    public function down(): void
    {
        Schema::table('hms_bookings', function (Blueprint $table): void {
            $table->dropColumn(['cancelled_at', 'cancel_reason']);
        });

        Schema::table('hms_memberships', function (Blueprint $table): void {
            $table->dropColumn('cancelled_at');
        });
    }
};
