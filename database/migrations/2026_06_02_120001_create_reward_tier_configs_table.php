<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_tier_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_tier_id')->constrained('reward_tiers')->cascadeOnDelete();
            $table->decimal('total_discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('free_checkup_count')->default(0);
            $table->unsignedInteger('earned_coins_per_booking')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tier_configs');
    }
};
