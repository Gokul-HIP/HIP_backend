<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardTierConfig extends Model
{
    protected $fillable = [
        'reward_tier_id',
        'total_discount_percentage',
        'free_checkup_count',
        'earned_coins_per_booking',
    ];

    protected $casts = [
        'total_discount_percentage' => 'float',
        'free_checkup_count' => 'integer',
        'earned_coins_per_booking' => 'integer',
    ];

    public function rewardTier(): BelongsTo
    {
        return $this->belongsTo(RewardTier::class);
    }
}
