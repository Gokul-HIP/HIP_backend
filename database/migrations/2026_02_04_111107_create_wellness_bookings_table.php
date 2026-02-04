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
        Schema::create('wellness_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile_number', 15)->nullable();
            $table->foreignId('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->foreignId('center_id')->nullable()->nullable()->constrained('wellness_centres')->nullOnDelete();
            $table->string('consultation_type')->nullable()->default('In-Person');
            $table->enum('status', ['pending','confirmed','cancelled','completed'])
                  ->default('pending');
            $table->text('purpose')->nullable();
            
            $table->index('member_id');
            $table->index('center_id');
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wellness_bookings');
    }
};
