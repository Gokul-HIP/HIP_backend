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
        Schema::create('diagnostics', function (Blueprint $table) {
            $table->id();
            $table->string('diagnostic_center_name');
            $table->string('diagnostic_center_address');
            $table->string('diagnostic_logo');
            $table->string('diagnostic_contact_person_name');
            $table->string('diagnostic_contact_person_number');
            $table->string('diagnostic_contact_person_email')->unique();
            $table->string('diagnostic_contcat_person_address');
            $table->string('diagnostic_contact_person_longitude');
            $table->string('diagnostic_contact_person_latitude');
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
        Schema::dropIfExists('diagnostics');
    }
};
