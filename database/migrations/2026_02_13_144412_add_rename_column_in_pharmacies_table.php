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
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->renameColumn('pharmacy_name', 'name');
            $table->renameColumn('pharmacy_address', 'address');
            $table->renameColumn('pharmacy_license_number', 'license_number');
            $table->renameColumn('pharmacy_gst_num', 'gst_number');
            $table->renameColumn('pharmacy_contact_person_name', 'contact_person_name');
            $table->renameColumn('pharmacy_contact_person_number', 'contact_person_number');
            $table->renameColumn('pharmacy_contact_person_email', 'contact_person_email');
            $table->renameColumn('pharmacy_opening_time', 'opening_time');
            $table->renameColumn('pharmacy_closing_time', 'closing_time');
            $table->renameColumn('pharmacy_logo', 'logo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->renameColumn('name', 'pharmacy_name');
            $table->renameColumn('address', 'pharmacy_address');
            $table->renameColumn('license_number', 'pharmacy_license_number');
            $table->renameColumn('gst_number', 'pharmacy_gst_num');
            $table->renameColumn('contact_person_name', 'pharmacy_contact_person_name');
            $table->renameColumn('contact_person_number', 'pharmacy_contact_person_number');
            $table->renameColumn('contact_person_email', 'pharmacy_contact_person_email');
            $table->renameColumn('opening_time', 'pharmacy_opening_time');
            $table->renameColumn('closing_time', 'pharmacy_closing_time');
            $table->renameColumn('logo', 'pharmacy_logo');
        });
    }
};
