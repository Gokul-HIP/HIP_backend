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
        Schema::create('hospital_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->foreignUuid('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->text('review')->nullable();
            $table->integer('rating')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospital_reviews');
    }
};
