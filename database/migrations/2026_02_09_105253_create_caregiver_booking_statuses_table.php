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
        Schema::create('caregiver_booking_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caregiver_booking_id')->constrained('caregiver_bookings')->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('notes_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->userstamps();
            $table->timestamps();

            $table->index('caregiver_booking_id');
            $table->index('changed_by');
            $table->index('notes_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caregiver_booking_statuses');
    }
};
