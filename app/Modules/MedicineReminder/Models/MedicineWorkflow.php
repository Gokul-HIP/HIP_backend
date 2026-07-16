<?php

namespace App\Modules\MedicineReminder\Models;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineWorkflow extends Model
{
    protected $table = 'medicine_workflows';

    protected $fillable = [
        'organization_id',
        'name',
        'status',
        'configuration',
        'created_by',
    ];

    protected $casts = [
        'configuration' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(MedicineReminderSchedule::class, 'workflow_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
