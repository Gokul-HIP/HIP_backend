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
        Schema::create('procedures', function (Blueprint $table) {
            $table->id();
            $table->string('procedure_name');
            $table->foreignId('speciality_id')->nullable()->constrained('specialities')->onDelete('set null');
            $table->string('assign_doctor')->nullable();
            $table->text('description')->nullable();
            $table->string('estimated_time');
            $table->unsignedBigInteger('cost');
            $table->string('procedure_code')->unique();
            $table->string('status')->default('inactive');
            $table->foreignUuid('hospital_id')->constrained('hospitals')->onDelete('cascade');
            $table->foreignUuid('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('procedure_master_id')->nullable()->constrained('procedure_masters')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procedures');
    }
};
