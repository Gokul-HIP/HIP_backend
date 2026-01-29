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
            $table->string('ownership')->nullable()->after('hospital_name');
            $table->string('establishment_type')->nullable()->after('ownership');
            $table->boolean('basic_details_completed')->default(false);
            $table->boolean('location_completed')->default(false);
            $table->boolean('capacity_completed')->default(false);
            $table->boolean('medical_completed')->default(false);
            $table->boolean('contact_completed')->default(false);
            $table->string('onboarding_status')->default('draft');
            $table->string('pincode')->nullable()->after('hospital_admin_address');
            $table->string('city')->nullable()->after('pincode');
            $table->string('area')->nullable()->after('city');
            $table->integer('bed_strength')->nullable()->after('area');
            $table->integer('icu_beds')->nullable()->after('bed_strength');
            $table->integer('operating_theatres')->nullable()->after('icu_beds');
            $table->integer('ambulance_available')->nullable()->after('operating_theatres');
            $table->string('registration_certificate')->nullable()->after('ambulance_available');
            $table->string('ownership_proof')->nullable()->after('registration_certificate');
            $table->string('accreditation_certificate')->nullable()->after('ownership_proof');
            $table->string('fire_safety_certificate')->nullable()->after('accreditation_certificate');
            $table->string('insurance_policy_number')->nullable()->after('fire_safety_certificate');
            $table->string('ownership_proof_doc')->nullable()->after('insurance_policy_number');
            $table->string('hospital_admin_emergency_contact')->nullable()->after('ownership_proof_doc');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn('ownership');
            $table->dropColumn('establishment_type');
            $table->dropColumn('basic_details_completed');
            $table->dropColumn('location_completed');
            $table->dropColumn('capacity_completed');
            $table->dropColumn('medical_completed');
            $table->dropColumn('contact_completed');
            $table->dropColumn('onboarding_status');
            $table->dropColumn('pincode');
            $table->dropColumn('city');
            $table->dropColumn('area');
            $table->dropColumn('bed_strength');
            $table->dropColumn('icu_beds');
            $table->dropColumn('operating_theatres');
            $table->dropColumn('ambulance_available');
            $table->dropColumn('registration_certificate');
            $table->dropColumn('ownership_proof');
            $table->dropColumn('accreditation_certificate');
            $table->dropColumn('fire_safety_certificate');
            $table->dropColumn('insurance_policy_number');
            $table->dropColumn('ownership_proof_doc');
            $table->dropColumn('hospital_admin_emergency_contact');
        });
    }
};
