<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reward_points_transitions', function (Blueprint $table) {
            $table->integer('coins_earned')->nullable()->after('points_changed');
        });
    }

    public function down(): void
    {
        Schema::table('reward_points_transitions', function (Blueprint $table) {
            $table->dropColumn('coins_earned');
        });
    }
};
