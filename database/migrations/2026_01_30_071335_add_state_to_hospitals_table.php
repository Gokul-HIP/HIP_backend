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
            $table->string('hospital_admin_pincode')->nullable()->after('pincode');
            $table->string('state')->nullable()->after('hospital_admin_pincode');
            $table->string('basic_details_status')->default('draft')->after('state');
            $table->string('location_status')->default('draft')->after('basic_details_status');
            $table->string('capacity_status')->default('draft')->after('location_status');
            $table->string('medical_status')->default('draft')->after('capacity_status');
            $table->string('contact_status')->default('draft')->after('medical_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->dropColumn('hospital_admin_pincode');
            $table->dropColumn('basic_details_status');
            $table->dropColumn('location_status');
            $table->dropColumn('capacity_status');
            $table->dropColumn('medical_status');
            $table->dropColumn('contact_status');
        });
    }
};
