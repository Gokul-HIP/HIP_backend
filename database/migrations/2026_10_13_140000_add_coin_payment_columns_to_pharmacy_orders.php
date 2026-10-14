<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_orders', 'coins_applied')) {
                $table->unsignedInteger('coins_applied')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'coins_discount')) {
                $table->decimal('coins_discount', 12, 2)->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'amount_paid')) {
                $table->decimal('amount_paid', 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            foreach (['coins_applied', 'coins_discount', 'amount_paid'] as $column) {
                if (Schema::hasColumn('pharmacy_orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
