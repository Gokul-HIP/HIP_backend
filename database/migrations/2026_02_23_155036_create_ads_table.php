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
        Schema::create('ads', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->enum('media_type', ['image', 'video'])->nullable();
            $table->string('media_url')->nullable();
            $table->foreignUuid('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->enum('redirect_type', ['internal', 'external'])->nullable();
            $table->enum('priority_type', ['high', 'medium', 'low'])->nullable();
            $table->string('redirect_url')->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->integer('priority')->default(0);
            $table->enum('status', ['active', 'inactive','pending','draft','completed','stopped'])->default('pending');
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ads');
    }
};
