<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_points_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('hip_user_id')->index();
            $table->integer('previous_balance');
            $table->integer('current_balance');
            $table->integer('points_changed');
            $table->string('change_type');
            $table->string('reward_source');
            $table->unsignedBigInteger('hospital_id')->nullable()->index();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->unsignedBigInteger('transaction_id')->nullable();
            $table->string('booking_source')->nullable();
            $table->string('booking_id')->nullable();
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_points_transitions');
    }
};
