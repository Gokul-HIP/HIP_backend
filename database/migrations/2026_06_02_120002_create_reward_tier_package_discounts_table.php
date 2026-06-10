<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_tier_package_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reward_tier_id')->constrained('reward_tiers')->cascadeOnDelete();
            $table->foreignId('diagnostic_package_id')->constrained('diagnostic_packages')->cascadeOnDelete();
            $table->enum('promotion_type', ['discount', 'free']);
            $table->enum('discount_type', ['percentage', 'flat'])->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tier_package_discounts');
    }
};
