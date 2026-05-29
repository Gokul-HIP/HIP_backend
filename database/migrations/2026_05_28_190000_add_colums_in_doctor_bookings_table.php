<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->boolean('is_coins_applied')->default(false)->after('message');
            $table->integer('coins_used')->default(0)->nullable()->after('is_coins_applied');
            $table->decimal('total_amount', 10, 2)->default(0)->nullable()->after('coins_used');
            $table->decimal('total_discount', 10, 2)->default(0)->nullable()->after('total_amount');
            $table->decimal('service_charges', 10, 2)->default(0)->nullable()->after('total_discount');
            $table->decimal('consultation_fee', 10, 2)->default(0)->nullable()->after('service_charges');
            $table->decimal('amount_after_discount', 10, 2)->default(0)->nullable()->after('consultation_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctor_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'is_coins_applied',
                'coins_used',
                'total_amount',
                'total_discount',
                'service_charges',
                'consultation_fee',
                'amount_after_discount',
            ]);
        });
    }
};
