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
        Schema::table('disease_packages', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('disease_packages', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('organization_id');
            $table->uuid('updated_by')->nullable()->after('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('disease_packages', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('disease_packages', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('organization_id');
            $table->foreignId('updated_by')->nullable()->after('created_by');
        });
    }
};
