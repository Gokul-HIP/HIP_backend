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
        Schema::create('content_target_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('content_moderations')->cascadeOnDelete();
            $table->foreignId('location_master_id')->constrained('location_masters')->cascadeOnDelete();
            $table->timestamps();

            $table->index('location_master_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_target_areas');
    }
};
