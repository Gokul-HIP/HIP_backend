<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('persons')
            ->select('mrn')
            ->whereNotNull('mrn')
            ->groupBy('mrn')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('mrn');

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot add a unique index on persons.mrn. Duplicate non-null values: '.$duplicates->implode(', ')
            );
        }

        Schema::table('persons', function (Blueprint $table) {
            $table->string('mrn', 64)->nullable()->change();
        });

        if (! $this->columnIsIndexed('persons', 'mrn')) {
            Schema::table('persons', function (Blueprint $table) {
                $table->unique('mrn');
            });
        }

        if (! $this->columnIsIndexed('persons', 'mobile')) {
            Schema::table('persons', function (Blueprint $table) {
                $table->index('mobile');
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('persons', 'persons_mrn_unique')) {
            Schema::table('persons', function (Blueprint $table) {
                $table->dropUnique('persons_mrn_unique');
            });
        }

        if ($this->indexExists('persons', 'persons_mobile_index')) {
            Schema::table('persons', function (Blueprint $table) {
                $table->dropIndex('persons_mobile_index');
            });
        }

        Schema::table('persons', function (Blueprint $table) {
            $table->string('mrn', 20)->nullable()->change();
        });
    }

    private function columnIsIndexed(string $table, string $column): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (in_array($column, $index['columns'], true)) {
                return true;
            }
        }

        return false;
    }

    private function indexExists(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }
};
