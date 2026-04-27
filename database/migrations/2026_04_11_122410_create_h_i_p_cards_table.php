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
        Schema::create('h_i_p_cards', function (Blueprint $table) {
            $table->id();
            $table->string('patient_id')->nullable();
            $table->string('hip_card_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('gender')->nullable();
            $table->decimal('paid_amount', 10, 2)->nullable();
            $table->integer('hip_points')->nullable();
            $table->integer('hip_points_used')->nullable();
            $table->timestamp('nfc_login_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('h_i_p_cards');
    }
};
