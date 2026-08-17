<?php

namespace App\Modules\Workflow\Models;

use App\Models\Hospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = [
        'organization_id',
        'hospital_id',
        'name',
        'status',
        'trigger_type',
        'current_version_id',
        'draft_version_id',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected $casts = [
        'organization_id' => 'integer',
        'hospital_id' => 'integer',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'current_version_id');
    }

    public function draftVersion(): BelongsTo
    {
        return $this->belongsTo(WorkflowVersion::class, 'draft_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }
}
