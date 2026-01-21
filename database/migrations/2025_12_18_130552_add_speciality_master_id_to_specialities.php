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
        Schema::table('specialities', function (Blueprint $table) {
            $table->foreignId('speciality_master_id')->nullable()->after('id')->constrained('specialities_masters')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('specialities', function (Blueprint $table) {
            $table->dropForeign(['speciality_master_id']);
            $table->dropColumn('speciality_master_id');
        });
    }
};
