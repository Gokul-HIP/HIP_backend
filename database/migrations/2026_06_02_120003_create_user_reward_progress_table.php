<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_reward_progress', function (Blueprint $table) {
            $table->id();
            $table->uuid('hip_user_id');
            $table->foreign('hip_user_id')->references('id')->on('healthinpocket_users')->cascadeOnDelete();
            $table->foreignId('reward_tier_id')->constrained('reward_tiers');
            $table->unsignedInteger('earned_coins_in_tier')->default(0);
            $table->unsignedInteger('total_lifetime_coins')->default(0);
            $table->timestamps();

            $table->unique('hip_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_reward_progress');
    }
};
