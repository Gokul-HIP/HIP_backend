<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remap schedule workflow_id from medicine_workflows → generic workflows (bridged rows).
        if (Schema::hasTable('workflows') && Schema::hasTable('medicine_reminder_schedules')) {
            $driver = Schema::getConnection()->getDriverName();

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement('
                    UPDATE medicine_reminder_schedules AS mrs
                    INNER JOIN workflows AS w
                        ON w.source_type = \'medicine_workflows\'
                        AND w.source_id = mrs.workflow_id
                    SET mrs.workflow_id = w.id
                ');
            }
        }

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->foreign('workflow_id')
                ->references('id')
                ->on('workflows')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->dropForeign(['workflow_id']);
        });

        Schema::table('medicine_reminder_schedules', function (Blueprint $table) {
            $table->foreign('workflow_id')
                ->references('id')
                ->on('medicine_workflows')
                ->cascadeOnDelete();
        });
    }
};
