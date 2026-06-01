<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('second_opinion_id')
                ->nullable()
                ->after('doctor_booking_id');
            $table->foreign('second_opinion_id')
                ->references('id')
                ->on('second_opinions')
                ->nullOnDelete();
        });

        if (Schema::hasColumn('second_opinions', 'invoice_id')) {
            Schema::table('second_opinions', function (Blueprint $table) {
                $table->dropColumn('invoice_id');
            });
        }

        if (! Schema::hasColumn('second_opinions', 'invoice_id')) {
            Schema::table('second_opinions', function (Blueprint $table) {
                $table->unsignedBigInteger('invoice_id')
                    ->nullable()
                    ->after('is_online_payment');
                $table->foreign('invoice_id')
                    ->references('id')
                    ->on('invoices')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('second_opinions', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropColumn('invoice_id');
        });

        Schema::table('second_opinions', function (Blueprint $table) {
            $table->string('invoice_id')->nullable();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['second_opinion_id']);
            $table->dropColumn('second_opinion_id');
        });
    }
};
