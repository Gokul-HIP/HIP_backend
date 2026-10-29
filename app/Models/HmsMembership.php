<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HmsMembership extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'integration_id',
        'hms_membership_id',
        'subscription_id',
        'plan_id',
        'hip_user_id',
        'hospital_id',
        'type',
        'amount_paid',
        'expected_amount',
        'price_mismatch',
        'payment_reference',
        'request_hash',
        'cancelled_at',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'price_mismatch' => 'boolean',
        'cancelled_at' => 'datetime',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(HmsIntegration::class, 'integration_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserFamilySubscription::class, 'subscription_id');
    }
}
