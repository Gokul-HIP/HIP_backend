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
        Schema::create('doctor_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('hospital_id')->constrained('hospitals')->onDelete('cascade');
            $table->string('day');
            $table->date('date');
            $table->json('time_slots');
            $table->json('procedure_ids');
            $table->text('notes')->nullable();
            $table->string('status')->default('active');
            $table->userstamps();
            $table->timestamps();
            $table->unique(['doctor_id', 'hospital_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_assignments');
    }
};
