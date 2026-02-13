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
            $table->renameColumn('diagnostic_center_name', 'name');
            $table->renameColumn('diagnostic_center_address', 'address');
            $table->renameColumn('diagnostic_logo', 'logo');
            $table->renameColumn('diagnostic_contact_person_name', 'contact_person_name');
            $table->renameColumn('diagnostic_contact_person_number', 'contact_person_number');
            $table->renameColumn('diagnostic_contact_person_email', 'contact_person_email');
            $table->renameColumn('diagnostic_contcat_person_address', 'contact_person_address');
            $table->renameColumn('diagnostic_contact_person_longitude', 'contact_person_longitude');
            $table->renameColumn('diagnostic_contact_person_latitude', 'contact_person_latitude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('diagnostics', function (Blueprint $table) {
            $table->renameColumn('name', 'diagnostic_center_name');
            $table->renameColumn('address', 'diagnostic_center_address');
            $table->renameColumn('logo', 'diagnostic_logo');
            $table->renameColumn('contact_person_name', 'diagnostic_contact_person_name');
            $table->renameColumn('contact_person_number', 'diagnostic_contact_person_number');
            $table->renameColumn('contact_person_email', 'diagnostic_contact_person_email');
            $table->renameColumn('contact_person_address', 'diagnostic_contcat_person_address');
            $table->renameColumn('contact_person_longitude', 'diagnostic_contact_person_longitude');
            $table->renameColumn('contact_person_latitude', 'diagnostic_contact_person_latitude');
        });
    }
};
