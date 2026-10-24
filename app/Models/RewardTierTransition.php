<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardTierTransition extends Model
{
    protected $fillable = [
        'hip_user_id',
        'previous_tier_id',
        'new_tier_id',
        'occurrence_id',
    ];

    protected $casts = [
        'previous_tier_id' => 'integer',
        'new_tier_id' => 'integer',
    ];
}
