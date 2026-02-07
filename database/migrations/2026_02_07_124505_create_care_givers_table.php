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
        Schema::create('care_givers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('category')->nullable();
            $table->json('qualification')->nullable();
            $table->string('working_since')->nullable();
            $table->text('about')->nullable();
            $table->foreignId('wellness_center_id')->nullable()->constrained('wellness_centres')->nullOnDelete();
            $table->string('mobile_number', 15)->nullable();
            $table->string('whatsapp_number', 15)->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('image')->nullable();
            $table->json('gallery')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->boolean('is_active')->default(true);
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_givers');
    }
};
