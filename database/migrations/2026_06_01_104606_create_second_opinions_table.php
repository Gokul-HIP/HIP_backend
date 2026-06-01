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
        Schema::create('second_opinions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->string('patient_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('diagnosis')->nullable();
            $table->string('treatment')->nullable();
            $table->string('question_for_doctor')->nullable();
            $table->json('document_ids')->nullable();
            $table->foreignId('branch_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('speciality_id')->nullable()->constrained('specialities_masters')->nullOnDelete();
            $table->foreignUuid('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->string('mode_of_consultation')->nullable();
            $table->date('preferred_date')->nullable();
            $table->json('preferred_time_slots')->nullable();
            $table->enum('status', ['pending','confirmed','cancelled','completed'])
                  ->default('pending');
            $table->string('relationship')->nullable();
            $table->boolean('is_coins_applied')->default(false);
            $table->integer('coins_used')->default(0)->nullable();
            $table->decimal('total_amount', 10, 2)->default(0)->nullable();
            $table->decimal('total_discount', 10, 2)->default(0)->nullable();
            $table->decimal('service_charges', 10, 2)->default(0);
            $table->decimal('consultation_fee', 10, 2)->default(0)->nullable();
            $table->decimal('amount_after_discount', 10, 2)->default(0)->nullable();
            $table->boolean('is_online_payment')->default(false);
            $table->string('payment_status')->nullable();
            $table->userstampsUuid();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('second_opinions');
    }
};
