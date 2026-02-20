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
        Schema::create('content_moderations', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('media_file')->nullable();
            $table->foreignId('speciality_id')->nullable()->constrained('specialities_masters')->nullOnDelete();
            $table->string('category')->nullable();
            $table->json('area_ids')->nullable();
            $table->foreignId('hospital_id')->nullable()->constrained('hospitals')->nullOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->nullOnDelete();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->nullOnDelete();
            $table->enum('status', ['draft', 'active', 'inactive', 'scheduled'])->default('draft');
            $table->boolean('is_published')->default(false);
            $table->json('schedule_time_data')->nullable();
            $table->integer('like_count')->default(0)->nullable();
            $table->integer('view_count')->default(0)->nullable();
            $table->integer('comment_count')->default(0)->nullable();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_moderations');
    }
};
