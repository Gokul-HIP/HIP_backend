<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_test_bookings', 'patient_id')) {
                $table->uuid('patient_id')->nullable()->after('member_id');
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'package_id')) {
                $table->foreignId('package_id')
                    ->nullable()
                    ->after('diagnostic_center_id')
                    ->constrained('diagnostic_packages')
                    ->nullOnDelete();
            }
        });

        // Allow member_id to store dependent (persons) UUID as well as HIP user UUID.
        if (Schema::hasTable('diagnostic_test_bookings')) {
            try {
                Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
                    $table->dropForeign(['member_id']);
                });
            } catch (\Throwable) {
                // FK may already be removed on this environment.
            }
        }
    }

    public function down(): void
    {
        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_test_bookings', 'package_id')) {
                $table->dropConstrainedForeignId('package_id');
            }

            if (Schema::hasColumn('diagnostic_test_bookings', 'patient_id')) {
                $table->dropColumn('patient_id');
            }
        });
    }
};
