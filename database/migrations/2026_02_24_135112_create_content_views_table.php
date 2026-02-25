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
        Schema::create('content_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('content_moderations')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->index(['content_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_views');
    }
};
