<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Disease-checker chatbot sessions.
 *
 * Named disease_chatbot_* because chatbot_sessions / chatbot_messages already
 * exist for the OpenRouter workflow chatbot (2026_09_04_120000_*).
 * Column types match healthinpocket_users.id (uuid), persons.id (uuid),
 * hospitals.id (bigint).
 *
 * Do not run on production from this task — migrations are written only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_chatbot_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('healthinpocket_users')->cascadeOnDelete();
            $table->foreignUuid('person_id')->nullable()->constrained('persons')->nullOnDelete();
            $table->foreignId('hospital_id')->constrained('hospitals')->cascadeOnDelete();
            $table->string('state');
            $table->json('context')->nullable();
            $table->string('status'); // active|completed|expired
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('last_activity_at');
        });

        Schema::create('disease_chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('session_id')
                ->constrained('disease_chatbot_sessions')
                ->cascadeOnDelete();
            $table->string('sender'); // bot|user
            $table->string('type');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['session_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_chatbot_messages');
        Schema::dropIfExists('disease_chatbot_sessions');
    }
};
