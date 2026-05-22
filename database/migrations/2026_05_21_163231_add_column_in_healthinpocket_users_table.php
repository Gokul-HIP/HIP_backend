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
        Schema::table('healthinpocket_users', function (Blueprint $table) {
            $table->string('marital_status')->nullable()->after('dob');
            $table->string('blood_group')->nullable()->after('last_name');
            $table->foreignId('preferred_branch_id')->nullable()->constrained('hospitals')->nullOnDelete()->after('blood_group');
            $table->string('emergency_contact_person_name')->nullable()->after('preferred_branch_id');
            $table->string('emergency_contact_person_phone')->nullable()->after('emergency_contact_person_name');
            $table->string('emergency_contact_person_relationship')->nullable()->after('emergency_contact_person_phone');
            $table->string('house_number')->nullable()->after('blood_group');
            $table->string('street')->nullable()->after('house_number');
            $table->string('city')->nullable()->after('street');
            $table->string('state')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('state');
            $table->timestamp('mobile_verified_at')->nullable()->after('otp_expires');
            $table->timestamp('email_verified_at')->nullable()->after('mobile_verified_at');
            $table->string('email_verification_token')->nullable()->after('email_verified_at');
            $table->timestamp('email_verification_token_expires_at')->nullable()->after('email_verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('healthinpocket_users', function (Blueprint $table) {

            $table->dropConstrainedForeignId('preferred_branch_id');

            $table->dropColumn([
                'marital_status',
                'blood_group',
                'emergency_contact_person_name',
                'emergency_contact_person_phone',
                'emergency_contact_person_relationship',
                'house_number',
                'street',
                'city',
                'state',
                'zip_code',
                'mobile_verified_at',
                'email_verified_at',
                'email_verification_token',
                'email_verification_token_expires_at'
            ]);
        });
    }
};
