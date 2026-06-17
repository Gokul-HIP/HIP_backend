<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsageLog extends Model
{
    protected $fillable = [
        'user_family_subscription_id',
        'hip_user_id',
        'usage_type',
        'booking_id',
        'booking_type',
        'used_at',
        'notes',
    ];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserFamilySubscription::class, 'user_family_subscription_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }
}
