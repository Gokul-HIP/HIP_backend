<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('diagnostic_packages', function (Blueprint $table) {
            $table->text('preparation_instruction')->nullable()->after('description');
            $table->text('terms_and_conditions')->nullable()->after('preparation_instruction');
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_packages', function (Blueprint $table) {
            $table->dropColumn(['preparation_instruction', 'terms_and_conditions']);
        });
    }
};
