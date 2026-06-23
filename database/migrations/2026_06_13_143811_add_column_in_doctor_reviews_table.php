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
        if (! Schema::hasColumn('doctor_reviews', 'quick_tags')) {
            Schema::table('doctor_reviews', function (Blueprint $table) {
                $table->string('quick_tags')->nullable()->after('review');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('doctor_reviews', 'quick_tags')) {
            Schema::table('doctor_reviews', function (Blueprint $table) {
                $table->dropColumn('quick_tags');
            });
        }
    }
};
