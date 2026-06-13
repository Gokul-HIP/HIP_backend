<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('second_opinion_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('second_opinion_id')->constrained('second_opinions')->cascadeOnDelete();

            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->foreignUuid('changed_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignUuid('notes_by')->nullable()->constrained('healthinpocket_users')->nullOnDelete();
            $table->userstampsUuid();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('second_opinion_statuses');
    }
};
