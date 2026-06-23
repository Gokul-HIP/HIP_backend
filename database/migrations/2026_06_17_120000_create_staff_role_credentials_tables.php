<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacist_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->timestamps();

            $table->index('hospital_id');
            $table->index('organization_id');
        });

        Schema::create('technician_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->timestamps();

            $table->index('hospital_id');
            $table->index('organization_id');
        });

        Schema::create('receptionist_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->timestamps();

            $table->index('hospital_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receptionist_credentials');
        Schema::dropIfExists('technician_credentials');
        Schema::dropIfExists('pharmacist_credentials');
    }
};
