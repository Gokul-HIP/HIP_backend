<?php

use App\Support\DiscountPrice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['diagnostic_lab_tests', 'lab_test_masters'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'test_discount')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('test_discount', 10, 2)->nullable()->change();
            });

            $rows = DB::table($table)
                ->select('id', 'test_price', 'test_discount')
                ->whereNotNull('test_discount')
                ->where('test_discount', '>', 0)
                ->get();

            foreach ($rows as $row) {
                $actual = (float) $row->test_price;
                $stored = (float) $row->test_discount;
                $discounted = DiscountPrice::migrateLegacyPercentage($actual, $stored);

                DB::table($table)->where('id', $row->id)->update([
                    'test_discount' => $discounted > 0 && $discounted < $actual ? $discounted : null,
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (['diagnostic_lab_tests', 'lab_test_masters'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'test_discount')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->decimal('test_discount', 5, 2)->nullable()->change();
            });
        }
    }
};
