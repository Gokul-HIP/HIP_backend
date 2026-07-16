<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_reminder_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('medicine_reminder_schedules')->cascadeOnDelete();
            $table->string('status')->index();
            $table->text('message')->nullable();
            $table->json('payload')->nullable();
            $table->unsignedInteger('execution_time')->nullable()->comment('Milliseconds');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_reminder_logs');
    }
};
