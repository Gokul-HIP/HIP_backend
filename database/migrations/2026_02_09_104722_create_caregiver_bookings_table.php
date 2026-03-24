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
        Schema::create('caregiver_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->foreignUuid('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->foreignId('caregiver_id')->nullable()->constrained('care_givers')->nullOnDelete();
            $table->foreignId('wellness_center_id')->nullable()->constrained('wellness_centres')->nullOnDelete();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->enum('status', ['pending','confirmed','cancelled','completed'])->default('pending');
            $table->text('purpose')->nullable();
            $table->userstamps();
            $table->timestamps();

            $table->index('member_id');
            $table->index('caregiver_id');
            $table->index('wellness_center_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caregiver_bookings');
    }
};
