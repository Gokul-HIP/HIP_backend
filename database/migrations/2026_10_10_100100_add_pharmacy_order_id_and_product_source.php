<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('invoices')
            && Schema::hasTable('pharmacy_orders')
            && ! Schema::hasColumn('invoices', 'pharmacy_order_id')
        ) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('pharmacy_order_id')
                    ->nullable()
                    ->constrained('pharmacy_orders')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('pharmacy_products') && ! Schema::hasColumn('pharmacy_products', 'source')) {
            Schema::table('pharmacy_products', function (Blueprint $table) {
                $table->string('source', 30)->default('admin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'pharmacy_order_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropConstrainedForeignId('pharmacy_order_id');
            });
        }

        if (Schema::hasTable('pharmacy_products') && Schema::hasColumn('pharmacy_products', 'source')) {
            Schema::table('pharmacy_products', function (Blueprint $table) {
                $table->dropColumn('source');
            });
        }
    }
};
