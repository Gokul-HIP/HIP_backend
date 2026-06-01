<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('diagnostic_test_bookings', 'package_id')) {
            try {
                Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
                    $table->dropForeign(['package_id']);
                });
            } catch (\Throwable) {
                // FK may already be removed.
            }
        }

        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('diagnostic_test_bookings', 'branch_id')) {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('member_id')
                    ->constrained('hospitals')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'package_type')) {
                $table->enum('package_type', ['diagnostic', 'disease'])
                    ->nullable()
                    ->after('package_id');
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'relationship')) {
                $table->string('relationship')->nullable()->after('patient_id');
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'is_coins_applied')) {
                $table->boolean('is_coins_applied')->default(false);
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'coins_used')) {
                $table->integer('coins_used')->default(0)->nullable();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'package_fee')) {
                $table->decimal('package_fee', 10, 2)->default(0)->nullable();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'service_charges')) {
                $table->decimal('service_charges', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'total_discount')) {
                $table->decimal('total_discount', 10, 2)->default(0)->nullable();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'amount_after_discount')) {
                $table->decimal('amount_after_discount', 10, 2)->default(0)->nullable();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->nullable();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'is_online_payment')) {
                $table->boolean('is_online_payment')->default(false);
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'invoice_id')) {
                $table->unsignedBigInteger('invoice_id')->nullable();
            }

            if (! Schema::hasColumn('diagnostic_test_bookings', 'payment_status')) {
                $table->string('payment_status')->nullable();
            }
        });

        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_test_bookings', 'invoice_id')) {
                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_test_bookings', 'invoice_id')) {
                $table->dropForeign(['invoice_id']);
            }

            $columns = [
                'branch_id',
                'package_type',
                'relationship',
                'is_coins_applied',
                'coins_used',
                'package_fee',
                'service_charges',
                'total_discount',
                'amount_after_discount',
                'total_amount',
                'is_online_payment',
                'invoice_id',
                'payment_status',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('diagnostic_test_bookings', $column)) {
                    if ($column === 'branch_id') {
                        $table->dropConstrainedForeignId('branch_id');
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });

        Schema::table('diagnostic_test_bookings', function (Blueprint $table) {
            if (Schema::hasColumn('diagnostic_test_bookings', 'package_id')) {
                $table->foreign('package_id')
                    ->references('id')
                    ->on('diagnostic_packages')
                    ->nullOnDelete();
            }
        });
    }
};
