<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_family_subscription_id')->constrained('user_family_subscriptions')->cascadeOnDelete();
            $table->foreignUuid('hip_user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->enum('usage_type', ['consultation', 'lab_test', 'hip_coins', 'checkup']);
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->string('booking_type')->nullable();
            $table->timestamp('used_at');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_family_subscription_id', 'usage_type'], 'sub_usage_logs_sub_id_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage_logs');
    }
};
