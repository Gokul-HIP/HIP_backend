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
        Schema::create('diagnostic_test_booking_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('diagnostic_test_booking_id')->nullable();
            $table->foreign('diagnostic_test_booking_id','dtb_status_booking_fk')->references('id')->on('diagnostic_test_bookings')->onDelete('cascade');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('notes_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_test_booking_statuses');
    }
};
