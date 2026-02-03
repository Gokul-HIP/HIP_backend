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
        Schema::create('wellness_centres', function (Blueprint $table) {
            $table->id();

            $table->string('centre_type')->nullable();          // Physical / Mental / Coaching etc
            $table->string('operating_mode')->nullable();       // Online / Offline / Hybrid

            $table->string('centre_name')->nullable();
            $table->string('age_group_served')->nullable();     // eg 18–65
            $table->text('description')->nullable();
            $table->string('languages_supported')->nullable();  // comma separated
            $table->string('target_audience')->nullable();      // Individual/Corporate/etc

            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pincode', 10)->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->string('centre_website')->nullable();
            $table->text('centre_instagram_links')->nullable();
            $table->text('centre_facebook_links')->nullable();
            $table->text('centre_linkedin_links')->nullable();
            $table->text('centre_twitter_links')->nullable();
            $table->text('centre_youtube_links')->nullable();

            $table->string('contact_person_name')->nullable();
            $table->string('contact_person_mobile', 15)->nullable();
            $table->string('contact_person_email')->nullable();

 
            $table->string('business_registration_type')->nullable();
            $table->string('gst_number')->nullable();


            $table->string('registration_certificate')->nullable();
            $table->string('ownership_proof')->nullable();
            $table->string('accreditation_certificate')->nullable();  // NABH / ISO
            $table->string('fire_safety_certificate')->nullable();

            $table->string('insurance_coverage')->nullable(); // Public Liability etc

            $table->enum('status', ['active','inactive','pending'])
                  ->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_centers');
    }
};
