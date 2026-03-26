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
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('doctor_name');
            $table->string('mobile_number');
            $table->json('qualifications')->nullable();
            $table->string('working_since')->nullable();
            $table->string('email')->nullable();
            $table->json('publications')->nullable();
            $table->json('achievements')->nullable();
            $table->string('doctor_image');
            $table->string('gender');
            $table->json('speciality');
            $table->string('status')->default('inactive');
            $table->json('hospital_ids')->nullable();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->onDelete('cascade');
            $table->json('assigned_speciality')->nullable();
            $table->json('assigned_procedure')->nullable();
            $table->string('assigned_hospital')->nullable();
            $table->string('assigned_organization')->nullable();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
