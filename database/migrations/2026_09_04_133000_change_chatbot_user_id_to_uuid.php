<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('hospital_id')->index();
        });

        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('hospital_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('chatbot_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('hospital_id')->index();
        });

        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
        Schema::table('chatbot_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('hospital_id');
        });
    }
};
