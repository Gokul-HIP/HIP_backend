<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserRewardProgress extends Model
{
    protected $table = 'user_reward_progress';

    protected $fillable = [
        'hip_user_id',
        'reward_tier_id',
        'earned_coins_in_tier',
        'total_lifetime_coins',
    ];

    protected $casts = [
        'earned_coins_in_tier' => 'integer',
        'total_lifetime_coins' => 'integer',
    ];

    public function rewardTier(): BelongsTo
    {
        return $this->belongsTo(RewardTier::class);
    }

    public function healthinpocketUser(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }
}
