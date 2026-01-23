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
        Schema::table('procedures', function (Blueprint $table) {
            $table->string('image')->nullable()->after('procedure_code');
            $table->string('recovery_time')->nullable()->after('image');
            $table->string('success_rate')->nullable()->after('recovery_time');
            $table->string('hospitalization_days')->nullable()->after('success_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('procedures', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->dropColumn('recovery_time');
            $table->dropColumn('success_rate');
            $table->dropColumn('hospitalization_days');
        });
    }
};
