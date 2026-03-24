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
        Schema::create('diagnostic_test_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->foreignUuid('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->foreignId('diagnostic_center_id')->nullable()->constrained('diagnostics')->nullOnDelete();
            $table->enum('test_type', ['single', 'multi', 'package'])->nullable();
            $table->json('test_items')->nullable();
            $table->enum('sample_collection', ['home', 'lab'])->nullable();
            $table->date('booking_date')->nullable();
            $table->json('required_time_slots')->nullable();
            $table->enum('status', ['pending','confirmed','cancelled','completed'])->default('pending');
            $table->text('purpose')->nullable();
            $table->userstamps();
            $table->timestamps();
            $table->index(['status', 'booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_test_bookings');
    }
};
