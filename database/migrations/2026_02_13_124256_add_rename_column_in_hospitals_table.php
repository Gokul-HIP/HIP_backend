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
        Schema::table('hospitals', function (Blueprint $table) {
            $table->renameColumn('hospital_name', 'name');
            $table->renameColumn('hospital_address', 'address');
            $table->renameColumn('hospital_logo', 'logo');
            $table->renameColumn('hospital_admin_name', 'admin_name');
            $table->renameColumn('hospital_admin_contact', 'admin_contact');
            $table->renameColumn('hospital_admin_email', 'admin_email');
            $table->renameColumn('hospital_admin_address', 'admin_address');
            $table->renameColumn('hospital_admin_pincode', 'admin_pincode');
            $table->renameColumn('hospital_admin_emergency_contact', 'admin_emergency_contact');
            $table->renameColumn('hospital_admin_latitude', 'admin_latitude');
            $table->renameColumn('hospital_admin_longitude', 'admin_longitude');
            $table->renameColumn('hospital_subtitle', 'subtitle');
            $table->renameColumn('hospital_about', 'about');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->renameColumn('name', 'hospital_name');
            $table->renameColumn('address', 'hospital_address');
            $table->renameColumn('logo', 'hospital_logo');
            $table->renameColumn('admin_name', 'hospital_admin_name');
            $table->renameColumn('admin_contact', 'hospital_admin_contact');
            $table->renameColumn('admin_email', 'hospital_admin_email');
            $table->renameColumn('admin_address', 'hospital_admin_address');
            $table->renameColumn('admin_pincode', 'hospital_admin_pincode');
            $table->renameColumn('admin_emergency_contact', 'hospital_admin_emergency_contact');
            $table->renameColumn('admin_latitude', 'hospital_admin_latitude');
            $table->renameColumn('admin_longitude', 'hospital_admin_longitude');
            $table->renameColumn('subtitle', 'hospital_subtitle');
            $table->renameColumn('about', 'hospital_about');
        });
    }
};
