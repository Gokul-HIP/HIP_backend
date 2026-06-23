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
        if (! Schema::hasTable('diagnostic_packages') || ! Schema::hasColumn('diagnostic_packages', 'discount')) {
            return;
        }

        Schema::table('diagnostic_packages', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->nullable()->change();
        });

        $rows = DB::table('diagnostic_packages')
            ->select('id', 'price', 'discount')
            ->whereNotNull('discount')
            ->where('discount', '>', 0)
            ->get();

        foreach ($rows as $row) {
            $actual = (float) $row->price;
            $stored = (float) $row->discount;
            $discounted = DiscountPrice::migrateLegacyPercentage($actual, $stored);

            DB::table('diagnostic_packages')->where('id', $row->id)->update([
                'discount' => $discounted > 0 && $discounted < $actual ? $discounted : null,
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('diagnostic_packages') || ! Schema::hasColumn('diagnostic_packages', 'discount')) {
            return;
        }

        Schema::table('diagnostic_packages', function (Blueprint $table) {
            $table->decimal('discount', 5, 2)->nullable()->change();
        });
    }
};
