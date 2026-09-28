<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('command');
            $table->string('schedule_type');
            $table->string('schedule');
            $table->text('description')->nullable();
            $table->string('timezone')->default('Asia/Kolkata');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('last_status')->nullable();
            $table->unsignedInteger('last_duration_ms')->nullable();
            $table->text('last_error')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
            $table->index('command');
        });

        Schema::create('cron_job_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cron_job_id')->constrained('cron_jobs')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->longText('output')->nullable();
            $table->text('error')->nullable();
            $table->string('triggered_by')->default('scheduler');
            $table->timestamps();

            $table->index(['cron_job_id', 'status']);
            $table->index('started_at');
        });

        if (class_exists(Permission::class) && Schema::hasTable('permissions')) {
            foreach (['manage cron jobs', 'manage queues'] as $name) {
                Permission::findOrCreate($name, 'filament');
            }

            $superAdmin = Role::findOrCreate('super-admin', 'filament');
            $superAdmin->givePermissionTo(['manage cron jobs', 'manage queues']);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_job_runs');
        Schema::dropIfExists('cron_jobs');
    }
};
