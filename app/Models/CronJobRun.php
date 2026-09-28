<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronJobRun extends Model
{
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'cron_job_id',
        'started_at',
        'finished_at',
        'status',
        'duration_ms',
        'output',
        'error',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_ms' => 'integer',
        ];
    }

    public function cronJob(): BelongsTo
    {
        return $this->belongsTo(CronJob::class);
    }

    public function formattedDuration(): ?string
    {
        if ($this->duration_ms === null) {
            return null;
        }

        return number_format($this->duration_ms / 1000, 2).' sec';
    }
}
