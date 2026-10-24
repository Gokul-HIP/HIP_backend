<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RewardPointsTransition extends Model
{
    protected $fillable = [
        'hip_user_id',
        'previous_balance',
        'current_balance',
        'points_changed',
        'coins_earned',
        'change_type',
        'reward_source',
        'hospital_id',
        'organization_id',
        'invoice_id',
        'transaction_id',
        'booking_source',
        'booking_id',
        'occurrence_id',
    ];

    protected $casts = [
        'previous_balance' => 'integer',
        'current_balance' => 'integer',
        'points_changed' => 'integer',
        'coins_earned' => 'integer',
        'hospital_id' => 'integer',
        'organization_id' => 'integer',
        'invoice_id' => 'integer',
        'transaction_id' => 'integer',
    ];
}
