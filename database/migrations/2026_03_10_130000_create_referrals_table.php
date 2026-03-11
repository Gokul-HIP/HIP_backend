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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('referred_by_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('referred_to_doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('member_name')->nullable();
            $table->string('country_code', 10)->default('+91');
            $table->string('phone_number', 20)->nullable();
            $table->date('referral_date')->nullable();
            $table->string('insurance_member_id')->nullable();
            $table->text('medical_notes')->nullable();
            $table->string('save')->default('draft');
            $table->enum('status', ['pending', 'completed', 'accepted', 'rejected','progress'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};

