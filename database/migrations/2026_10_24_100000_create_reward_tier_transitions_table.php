<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_tier_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('hip_user_id')->index();
            $table->unsignedBigInteger('previous_tier_id')->nullable();
            $table->unsignedBigInteger('new_tier_id');
            $table->string('occurrence_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_tier_transitions');
    }
};
