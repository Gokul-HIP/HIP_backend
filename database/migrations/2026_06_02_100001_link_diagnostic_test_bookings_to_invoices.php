<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'diagnostic_test_booking_id')) {
                $table->unsignedBigInteger('diagnostic_test_booking_id')
                    ->nullable()
                    ->after('second_opinion_id');
                $table->foreign('diagnostic_test_booking_id')
                    ->references('id')
                    ->on('diagnostic_test_bookings')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'diagnostic_test_booking_id')) {
                $table->dropForeign(['diagnostic_test_booking_id']);
                $table->dropColumn('diagnostic_test_booking_id');
            }
        });
    }
};
