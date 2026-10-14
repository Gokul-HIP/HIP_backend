<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'created_by')) {
                $table->char('created_by', 36)->nullable();
            }
            if (! Schema::hasColumn('transactions', 'updated_by')) {
                $table->char('updated_by', 36)->nullable();
            }
        });

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (['created_by', 'updated_by'] as $column) {
            $type = $this->columnType('transactions', $column);
            if ($type !== null && preg_match('/int/i', $type) === 1) {
                DB::statement("ALTER TABLE `transactions` MODIFY `{$column}` CHAR(36) NULL");
            }
        }
    }

    public function down(): void
    {
        // created_by and updated_by stay. The original transactions migration adds them,
        // and stored values are user ids.
    }

    private function columnType(string $table, string $column): ?string
    {
        $match = collect(Schema::getColumns($table))->firstWhere('name', $column);

        return is_array($match) ? (string) ($match['type'] ?? '') : null;
    }
};
