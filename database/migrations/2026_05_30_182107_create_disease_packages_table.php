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
        Schema::create('disease_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('disease_id')->nullable()->constrained('diseases')->nullOnDelete();
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('discount', 5, 2)->nullable();
            $table->decimal('weight', 5, 2)->nullable();
            $table->string('image')->nullable();
            $table->string('status')->default('inactive');
            $table->json('lab_tests')->nullable();
            $table->boolean('is_home_service')->default(false);
            $table->foreignId('diagnostic_id')->constrained('diagnostics')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->userstampsUuid();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disease_packages');
    }
};
