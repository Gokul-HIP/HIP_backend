<?php

namespace App\Modules\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecution extends Model
{
    protected $fillable = [
        'workflow_id',
        'workflow_version_id',
        'status',
        'trigger_type',
        'current_node_id',
        'context',
        'variables',
        'started_at',
        'completed_at',
        'duration_ms',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'variables' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'workflow_version_id');
    }

    public function communicationLogs(): HasMany
    {
        return $this->hasMany(CommunicationLog::class);
    }
}
