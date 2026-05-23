<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('diagnostic_test_bookings')) {
            return;
        }

        try {
            Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
                $table->dropForeign(['patient_id']);
            });
        } catch (\Throwable) {
            // FK may not exist on this environment.
        }
    }

    public function down(): void
    {
        // Intentionally left empty — restoring FK could break existing rows.
    }
};
