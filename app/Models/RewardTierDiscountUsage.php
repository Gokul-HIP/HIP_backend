<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardTierDiscountUsage extends Model
{
    protected $fillable = [
        'hip_user_id', 'reward_tier_id', 'tier_generation', 'booking_type', 'booking_id', 'status', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
