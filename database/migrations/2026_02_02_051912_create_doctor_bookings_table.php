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
        Schema::create('doctor_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->foreignId('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->enum('status', ['pending','confirmed','cancelled','completed'])
                  ->default('pending');
        
            $table->index('member_id');
            $table->index('hospital_id');
            $table->index('doctor_id');
        
            $table->userstamps();
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_bookings');
    }
};
