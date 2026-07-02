<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            $table->text('clinical_notes')->nullable()->after('purpose');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('diagnostic_test_booking_id')
                ->nullable()
                ->after('patient_id')
                ->constrained('diagnostic_test_bookings')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('diagnostic_test_booking_id');
        });

        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            $table->dropColumn('clinical_notes');
        });
    }
};
