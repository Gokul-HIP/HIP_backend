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
        Schema::create('diagnostic_lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_name');
            $table->string('test_category');
            $table->string('test_code')->unique()->nullable();
            $table->text('test_description')->nullable();
            $table->decimal('test_price', 10, 2)->nullable();
            $table->decimal('test_discount', 5, 2)->nullable();
            $table->string('test_image')->nullable();
            $table->enum('test_status', ['active', 'inactive'])->default('inactive');
            $table->foreignId('diagnostic_id')->constrained('diagnostics')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('diagnostic_lab_tests');
    }
};
