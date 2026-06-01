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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('member_id')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->string('document_name')->nullable();
            $table->string('document_path')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_size')->nullable();
            $table->userstamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
