<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmsExternalIdMap extends Model
{
    public const TYPE_HOSPITAL = 'hospital';

    public const TYPE_DOCTOR = 'doctor';

    public const TYPE_PACKAGE = 'package';

    public const TYPE_PLAN = 'plan';

    public const TYPE_PATIENT = 'patient';

    protected $fillable = [
        'integration_id',
        'entity_type',
        'external_id',
        'local_id',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(HmsIntegration::class, 'integration_id');
    }
}
