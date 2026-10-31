<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmis_patient_savings', function (Blueprint $table) {
            $table->id();
            $table->string('person_id')->unique();
            $table->decimal('lifetime_discount_total', 14, 2)->default(0);
            $table->decimal('membership_discount_total', 14, 2)->default(0);
            $table->decimal('coins_value_total', 14, 2)->default(0);
            $table->decimal('lifetime_paid_total', 14, 2)->default(0);
            $table->unsignedInteger('transactions_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmis_patient_savings');
    }
};
