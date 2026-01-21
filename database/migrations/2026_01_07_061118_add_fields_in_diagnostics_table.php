<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            
            $table->decimal('diagnostic_contact_person_longitude', 10, 7)->nullable()->change();
            $table->decimal('diagnostic_contact_person_latitude', 10, 7)->nullable()->change();
            $table->foreignId('location_id')->nullable()->after('diagnostic_contact_person_latitude')->constrained('location_masters')->nullOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
            $table->decimal('diagnostic_contact_person_longitude', 10, 7)->nullable()->change();
            $table->decimal('diagnostic_contact_person_latitude', 10, 7)->nullable()->change();
        });
    }
};
