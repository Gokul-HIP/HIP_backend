<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wellness_centres', function (Blueprint $table) {
            $table->string('working_since')->after('age_group_served')->nullable();
            $table->json('working_days')->after('working_since')->nullable();
            $table->json('working_hours')->after('working_days')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wellness_centres', function (Blueprint $table) {
            $table->dropColumn(['working_since', 'working_days', 'working_hours']);
        });
    }
};
