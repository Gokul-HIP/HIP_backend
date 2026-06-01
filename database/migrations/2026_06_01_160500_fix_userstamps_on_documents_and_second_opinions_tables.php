<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['documents', 'second_opinions'] as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            if (! Schema::hasColumn($tableName, 'created_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['created_by', 'updated_by']);
            });

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $afterColumn = $tableName === 'documents' ? 'document_size' : 'payment_status';

                if (Schema::hasColumn($tableName, $afterColumn)) {
                    $table->uuid('created_by')->nullable()->after($afterColumn);
                    $table->uuid('updated_by')->nullable()->after('created_by');
                } else {
                    $table->uuid('created_by')->nullable();
                    $table->uuid('updated_by')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['documents', 'second_opinions'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'created_by')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['created_by', 'updated_by']);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
            });
        }
    }
};
