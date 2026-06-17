<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_type')->default('family_package');
            $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['hip_user_id', 'entity_id', 'entity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rewards');
    }
};
