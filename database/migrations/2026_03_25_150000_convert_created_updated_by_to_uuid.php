<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('healthinpocket_users')) {
            return;
        }

        $rows = DB::select("
            SELECT table_name, column_name, is_nullable
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND column_name IN ('created_by', 'updated_by')
              AND data_type = 'bigint'
        ");

        foreach ($rows as $row) {
            $table = $row->table_name;
            $column = $row->column_name;
            $isNullable = ($row->is_nullable === 'YES');

            // HIPUser ids are UUIDs, so audit columns must be able to store CHAR(36).
            $nullSql = $isNullable ? 'NULL' : 'NOT NULL';

            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` CHAR(36) {$nullSql};");
        }
    }

    public function down(): void
    {
        $rows = DB::select("
            SELECT table_name, column_name, is_nullable
            FROM information_schema.columns
            WHERE table_schema = DATABASE()
              AND column_name IN ('created_by', 'updated_by')
              AND data_type = 'char'
        ");

        foreach ($rows as $row) {
            $table = $row->table_name;
            $column = $row->column_name;
            $isNullable = ($row->is_nullable === 'YES');

            $nullSql = $isNullable ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED {$nullSql};");
        }
    }
};

