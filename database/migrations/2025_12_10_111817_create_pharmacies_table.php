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
        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->string('pharmacy_name');
            $table->string('pharmacy_id')->unique();
            $table->string('pharmacy_address');
            $table->string('pharmacy_license_number');
            $table->string('pharmacy_gst_num');
            $table->string('pharmacy_contact_person_name');
            $table->string('pharmacy_contact_person_number');
            $table->string('pharmacy_contact_person_email')->unique();
            $table->string('pharmacy_logo');
            $table->string('pharmacy_opening_time');
            $table->string('pharmacy_closing_time');
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('status')->default('inactive');
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pharmacies');
    }
};
