<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('appointment_reminder_schedules')) {
            Schema::create('appointment_reminder_schedules', function (Blueprint $table) {
                $table->id();
                $table->string('identity', 191);
                $table->string('booking_type', 40);
                $table->unsignedBigInteger('booking_id');
                $table->unsignedBigInteger('workflow_id');
                $table->unsignedBigInteger('workflow_version_id');
                $table->unsignedBigInteger('hospital_id');
                $table->unsignedBigInteger('organization_id')->nullable();
                $table->dateTime('appointment_at');
                $table->string('configured_timing', 40);
                $table->string('stage', 40);
                $table->dateTime('scheduled_at');
                $table->string('status', 20)->default('pending');
                $table->timestamps();
            });
        }

        // Explicit short names stay below MySQL's 64-character identifier cap.
        // Also repairs a table left behind if an earlier attempt failed while
        // adding one of the indexes after CREATE TABLE had already succeeded.
        $this->ensureIndex(['identity'], 'ars_identity_uq', true);
        $this->ensureIndex(['scheduled_at'], 'ars_scheduled_at_idx');
        $this->ensureIndex(['status'], 'ars_status_idx');
        $this->ensureIndex(['booking_type', 'booking_id', 'status'], 'ars_booking_status_idx');
        $this->ensureIndex(['workflow_id', 'workflow_version_id'], 'ars_workflow_version_idx');
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_reminder_schedules');
    }

    /** @param array<int, string> $columns */
    private function ensureIndex(array $columns, string $name, bool $unique = false): void
    {
        foreach (Schema::getIndexes('appointment_reminder_schedules') as $index) {
            $existingColumns = $index['columns'] ?? [];
            if ($existingColumns === $columns && (bool) ($index['unique'] ?? false) === $unique) {
                return;
            }
        }

        Schema::table('appointment_reminder_schedules', function (Blueprint $table) use ($columns, $name, $unique) {
            if ($unique) {
                $table->unique($columns, $name);
            } else {
                $table->index($columns, $name);
            }
        });
    }
};
