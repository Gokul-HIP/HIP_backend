<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            if (! Schema::hasColumn('persons', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('hospital_id');
            }

            if (! Schema::hasColumn('persons', 'deactivated_at')) {
                $table->timestamp('deactivated_at')->nullable()->after('is_active');
            }

            if (! Schema::hasColumn('persons', 'created_by')) {
                $table->uuid('created_by')->nullable()->after('deactivated_at');
            }

            if (! Schema::hasColumn('persons', 'updated_by')) {
                $table->uuid('updated_by')->nullable()->after('created_by');
            }
        });

        if (Schema::hasColumn('persons', 'is_active')) {
            DB::table('persons')->whereNull('is_active')->update(['is_active' => true]);
        }

        Schema::table('persons', function (Blueprint $table) {
            if (Schema::hasColumn('persons', 'is_active') && ! Schema::hasIndex('persons', 'persons_is_active_index')) {
                $table->index('is_active', 'persons_is_active_index');
            }

            if (Schema::hasColumn('persons', 'created_by') && ! Schema::hasIndex('persons', 'persons_created_by_index')) {
                $table->index('created_by', 'persons_created_by_index');
            }

            if (Schema::hasColumn('persons', 'updated_by') && ! Schema::hasIndex('persons', 'persons_updated_by_index')) {
                $table->index('updated_by', 'persons_updated_by_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('persons', function (Blueprint $table) {
            if (Schema::hasIndex('persons', 'persons_updated_by_index')) {
                $table->dropIndex('persons_updated_by_index');
            }

            if (Schema::hasIndex('persons', 'persons_created_by_index')) {
                $table->dropIndex('persons_created_by_index');
            }

            if (Schema::hasIndex('persons', 'persons_is_active_index')) {
                $table->dropIndex('persons_is_active_index');
            }
        });

        Schema::table('persons', function (Blueprint $table) {
            foreach (['updated_by', 'created_by', 'deactivated_at', 'is_active'] as $column) {
                if (Schema::hasColumn('persons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
