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
        Schema::create('content_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('content_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->text('comment');
            $table->enum('status', ['active', 'hidden', 'reported'])->default('active');
            $table->timestamps();

            $table->index(['content_id', 'created_at']);
            $table->foreign('content_id')->references('id')->on('content_moderations')->cascadeOnDelete();
            $table->foreign('member_id')->references('id')->on('healthinpocket_users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_comments');
    }
};
