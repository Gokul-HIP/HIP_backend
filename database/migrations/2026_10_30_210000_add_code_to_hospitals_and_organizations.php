<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hospitals') && ! Schema::hasColumn('hospitals', 'code')) {
            Schema::table('hospitals', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->unique();
            });
        }

        if (Schema::hasTable('organizations') && ! Schema::hasColumn('organizations', 'code')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->string('code', 50)->nullable()->unique();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hospitals') && Schema::hasColumn('hospitals', 'code')) {
            Schema::table('hospitals', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }

        if (Schema::hasTable('organizations') && Schema::hasColumn('organizations', 'code')) {
            Schema::table('organizations', function (Blueprint $table) {
                $table->dropUnique(['code']);
                $table->dropColumn('code');
            });
        }
    }
};
