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
        Schema::create('stem_cell_booking_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stem_cell_booking_id')->constrained('stem_cell_bookings')->cascadeOnDelete();
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
        Schema::dropIfExists('stem_cell_booking_statuses');
    }
};
