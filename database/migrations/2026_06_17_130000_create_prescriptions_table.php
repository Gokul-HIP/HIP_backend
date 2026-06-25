<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->uuid('patient_id')->nullable();
            $table->foreignUuid('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('doctor_booking_id')->nullable()->constrained('doctor_bookings')->nullOnDelete();
            $table->foreignId('follow_up_booking_id')->nullable()->constrained('doctor_bookings')->nullOnDelete();
            $table->json('medications')->nullable();
            $table->json('lab_tests')->nullable();
            $table->json('document_ids')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('vitals')->nullable();
            $table->text('diagnosis')->nullable();
            $table->string('status')->default('draft');
            $table->userstampsUuid();
            $table->timestamps();

            $table->foreign('patient_id')
                ->references('id')
                ->on('persons')
                ->nullOnDelete();

            $table->index(['doctor_id', 'patient_id']);
            $table->index('doctor_booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
