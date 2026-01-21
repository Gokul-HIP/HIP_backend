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
        Schema::table('hospitals', function (Blueprint $table) {
            $table->json('pharmacy_ids')->nullable()->after('location_id');
            $table->foreignId('diagnostic_center_id')->nullable()->constrained('diagnostics')->onDelete('cascade')->after('pharmacy_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hospitals', function (Blueprint $table) {
            $table->dropColumn('pharmacy_ids');
            $table->dropForeign(['diagnostic_center_id']);
             $table->dropColumn('diagnostic_center_id');
        });
    }
};
