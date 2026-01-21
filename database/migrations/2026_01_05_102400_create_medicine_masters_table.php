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
        Schema::create('medicine_masters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('dosage_form')->nullable();
            $table->string('strength')->nullable();
            $table->string('pack_size')->nullable();
            $table->decimal('mrp', 10, 2)->nullable();
            $table->decimal('selling_price', 10, 2)->nullable();
            $table->decimal('discount', 10, 2)->default(0)->nullable();
            $table->integer('stock_quantity')->default(0)->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('batch_number')->nullable();
            $table->boolean('prescription_required')->default(false);
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicine_masters');
    }
};
