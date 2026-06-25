<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('prescriptions')) {
            return;
        }

        if (! Schema::hasColumn('prescriptions', 'created_by')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->uuid('created_by')->nullable()->after('status');
            $table->uuid('updated_by')->nullable()->after('created_by');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('prescriptions')) {
            return;
        }

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'updated_by']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('status');
            $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
        });
    }
};
