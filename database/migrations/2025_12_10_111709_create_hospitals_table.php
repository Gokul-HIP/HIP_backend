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
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('hospital_name');
            $table->string('hospital_address');
            $table->string('hospital_logo');
            $table->string('hospital_admin_name');
            $table->string('hospital_admin_contact');
            $table->string('hospital_admin_email');
            $table->string('hospital_admin_address');
            $table->string('hospital_admin_longitude');
            $table->string('hospital_admin_latitude');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
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
        Schema::dropIfExists('hospitals');
    }
};
