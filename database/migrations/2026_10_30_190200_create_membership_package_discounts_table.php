<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_package_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_package_id')->constrained('family_packages')->cascadeOnDelete();
            $table->string('payment_type', 64);
            $table->decimal('percentage', 5, 2);
            $table->timestamps();

            $table->unique(['family_package_id', 'payment_type'], 'membership_package_discounts_package_type_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_package_discounts');
    }
};
