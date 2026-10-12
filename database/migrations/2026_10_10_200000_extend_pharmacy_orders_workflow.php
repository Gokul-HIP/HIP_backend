<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_orders', 'service_charge')) {
                $table->decimal('service_charge', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('pharmacy_orders', 'gateway_charge')) {
                $table->decimal('gateway_charge', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('pharmacy_orders', 'delivery_partner')) {
                $table->string('delivery_partner')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'rider_name')) {
                $table->string('rider_name')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'tracking_ref')) {
                $table->string('tracking_ref')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'expected_delivery_at')) {
                $table->timestamp('expected_delivery_at')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'in_review_at')) {
                $table->timestamp('in_review_at')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'reviewed_by')) {
                $table->uuid('reviewed_by')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'deleted_at')) {
                $table->softDeletes();
            }
            if (! Schema::hasColumn('pharmacy_orders', 'deleted_by')) {
                $table->uuid('deleted_by')->nullable();
            }
        });

        Schema::table('pharmacy_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_order_items', 'medicine_master_id')) {
                $table->foreignId('medicine_master_id')
                    ->nullable()
                    ->constrained('medicine_masters')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('pharmacy_order_items', 'dosage')) {
                $table->string('dosage')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_order_items', 'frequency')) {
                $table->string('frequency')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_order_items', 'duration')) {
                $table->string('duration')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_order_items', 'when_to_take')) {
                $table->string('when_to_take')->nullable();
            }
            if (! Schema::hasColumn('pharmacy_order_items', 'special_instruction')) {
                $table->text('special_instruction')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_order_items', function (Blueprint $table) {
            if (Schema::hasColumn('pharmacy_order_items', 'medicine_master_id')) {
                $table->dropConstrainedForeignId('medicine_master_id');
            }

            $columns = array_values(array_filter(
                ['dosage', 'frequency', 'duration', 'when_to_take', 'special_instruction'],
                fn (string $column) => Schema::hasColumn('pharmacy_order_items', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('pharmacy_orders', function (Blueprint $table) {
            $columns = array_values(array_filter(
                [
                    'service_charge',
                    'gateway_charge',
                    'delivery_partner',
                    'rider_name',
                    'tracking_ref',
                    'expected_delivery_at',
                    'in_review_at',
                    'dispatched_at',
                    'reviewed_by',
                    'deleted_at',
                    'deleted_by',
                ],
                fn (string $column) => Schema::hasColumn('pharmacy_orders', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
