<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hms_bookings', function (Blueprint $table): void {
            $table->string('refund_status', 32)->nullable()->after('cancel_reason');
            $table->decimal('refund_amount', 12, 2)->nullable()->after('refund_status');
            $table->unsignedInteger('coins_reversed')->nullable()->after('refund_amount');
        });
    }

    public function down(): void
    {
        Schema::table('hms_bookings', function (Blueprint $table): void {
            $table->dropColumn(['refund_status', 'refund_amount', 'coins_reversed']);
        });
    }
};
