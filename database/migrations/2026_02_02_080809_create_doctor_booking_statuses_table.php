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
        Schema::create('doctor_booking_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_booking_id')->constrained('doctor_bookings')->cascadeOnDelete();
    
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('notes_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_booking_statuses');
    }
};
