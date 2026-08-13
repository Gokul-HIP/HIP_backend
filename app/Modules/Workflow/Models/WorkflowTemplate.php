<?php

namespace App\Modules\Workflow\Models;

use App\Models\HIPUser;
use App\Models\Organization;
use App\Modules\Workflow\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Reusable React Flow workflow blueprints.
 * Never executed — copied into workflows / workflow_versions by the frontend.
 */
class WorkflowTemplate extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'slug',
        'description',
        'module',
        'trigger_type',
        'trigger_label',
        'category',
        'definition',
        'thumbnail',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'definition' => 'array',
            'status' => WorkflowStatus::class,
            'deleted_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'updated_by');
    }

    public function nodeCount(): int
    {
        $nodes = $this->definition['nodes'] ?? null;

        return is_array($nodes) ? count($nodes) : 0;
    }

    public function edgeCount(): int
    {
        $edges = $this->definition['edges'] ?? null;

        return is_array($edges) ? count($edges) : 0;
    }
}
