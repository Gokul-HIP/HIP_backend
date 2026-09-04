<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100)->index();
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('hospital_id')->nullable()->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['session_id', 'organization_id', 'hospital_id'],
                'chatbot_sessions_scope_unique'
            );
        });

        Schema::create('chatbot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 100);
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('hospital_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->unsignedBigInteger('workflow_id')->nullable();
            $table->unsignedBigInteger('execution_id')->nullable();
            $table->string('role', 20);
            $table->longText('content');
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamps();

            $table->index('session_id');
            $table->index('organization_id');
            $table->index('hospital_id');
            $table->index('created_at');
            $table->index(
                ['session_id', 'organization_id', 'hospital_id', 'created_at'],
                'chatbot_messages_scope_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_messages');
        Schema::dropIfExists('chatbot_sessions');
    }
};
