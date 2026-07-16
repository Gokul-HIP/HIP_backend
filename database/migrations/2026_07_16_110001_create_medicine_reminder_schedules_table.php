<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_reminder_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('medicine_workflows')->cascadeOnDelete();
            $table->uuid('patient_id')->nullable()->index();
            $table->foreignId('prescription_id')->constrained('prescriptions')->cascadeOnDelete();
            $table->unsignedInteger('prescription_item_id')->nullable()->comment('Index in prescriptions.medications JSON');
            $table->unsignedBigInteger('medicine_id')->nullable()->index();
            $table->dateTime('scheduled_at')->index();
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('next_retry_at')->nullable()->index();
            $table->json('channels')->nullable();
            $table->text('message_template')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('persons')->nullOnDelete();
            $table->index(['status', 'scheduled_at']);
            $table->index(['status', 'next_retry_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_reminder_schedules');
    }
};
