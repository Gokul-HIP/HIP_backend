<?php

namespace App\Models;

use App\Services\Cron\CronCommandRegistry;
use App\Services\Cron\CronSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CronJob extends Model
{
    protected $fillable = [
        'name',
        'command',
        'schedule_type',
        'schedule',
        'description',
        'timezone',
        'is_active',
        'last_run_at',
        'next_run_at',
        'last_status',
        'last_duration_ms',
        'last_error',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'last_duration_ms' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $job): void {
            $registry = app(CronCommandRegistry::class);
            $schedule = app(CronSchedule::class);

            $registry->assertApproved((string) $job->command);

            $job->timezone = $job->timezone ?: config('cron.timezone', 'Asia/Kolkata');
            $job->schedule = $schedule->expressionFor(
                (string) $job->schedule_type,
                $job->schedule_type === 'custom' ? $job->schedule : null
            );

            $job->next_run_at = $schedule->nextRunAt(
                $job->schedule,
                now(),
                $job->timezone
            );
        });
    }

    public function runs(): HasMany
    {
        return $this->hasMany(CronJobRun::class)->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'updated_by');
    }

    public function commandLabel(): string
    {
        return app(CronCommandRegistry::class)->label((string) $this->command);
    }

    public function scheduleLabel(): string
    {
        return app(CronSchedule::class)->label((string) $this->schedule_type, (string) $this->schedule);
    }

    public function formattedLastDuration(): ?string
    {
        if ($this->last_duration_ms === null) {
            return null;
        }

        return number_format($this->last_duration_ms / 1000, 2).' sec';
    }
}
