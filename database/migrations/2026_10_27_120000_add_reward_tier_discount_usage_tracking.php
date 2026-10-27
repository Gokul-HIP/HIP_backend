<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_reward_progress', function (Blueprint $table): void {
            $table->unsignedInteger('tier_generation')->default(0)->after('reward_tier_id');
        });

        Schema::create('reward_tier_discount_usages', function (Blueprint $table): void {
            $table->id();
            $table->uuid('hip_user_id');
            $table->foreignId('reward_tier_id')->constrained('reward_tiers')->cascadeOnDelete();
            $table->unsignedInteger('tier_generation');
            $table->string('booking_type', 32);
            $table->unsignedBigInteger('booking_id');
            $table->enum('status', ['reserved', 'consumed', 'released'])->default('reserved');
            $table->timestamps();

            $table->foreign('hip_user_id')->references('id')->on('healthinpocket_users')->cascadeOnDelete();
            $table->unique(['booking_type', 'booking_id'], 'rtd_usage_booking_unique');
            $table->index(['hip_user_id', 'reward_tier_id', 'tier_generation', 'status'], 'rtd_usage_count_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tier_discount_usages');
        Schema::table('user_reward_progress', function (Blueprint $table): void {
            $table->dropColumn('tier_generation');
        });
    }
};
