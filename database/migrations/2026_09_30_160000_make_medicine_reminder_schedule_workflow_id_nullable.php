<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('medicine_reminder_schedules')) {
            return;
        }

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('workflow_id')->nullable()->change();
        });

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->foreign('workflow_id')
                ->references('id')
                ->on('workflows')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('medicine_reminder_schedules')) {
            return;
        }

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->unsignedBigInteger('workflow_id')->nullable(false)->change();
        });

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->foreign('workflow_id')
                ->references('id')
                ->on('workflows')
                ->cascadeOnDelete();
        });
    }
};
