<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReward extends Model
{
    public const ENTITY_TYPE_FAMILY_PACKAGE = 'family_package';

    protected $table = 'user_rewards';

    protected $fillable = [
        'entity_id',
        'entity_type',
        'hip_user_id',
        'is_applied',
        'applied_at',
    ];

    protected $casts = [
        'is_applied' => 'boolean',
        'applied_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserFamilySubscription::class, 'entity_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }
}
