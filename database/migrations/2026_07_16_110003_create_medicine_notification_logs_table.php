<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('medicine_reminder_schedules')->cascadeOnDelete();
            $table->string('channel')->index();
            $table->string('status')->index();
            $table->text('response')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_notification_logs');
    }
};
