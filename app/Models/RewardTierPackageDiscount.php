<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardTierPackageDiscount extends Model
{
    protected $fillable = [
        'reward_tier_id',
        'diagnostic_package_id',
        'promotion_type',
        'discount_type',
        'discount_value',
    ];

    protected $casts = [
        'discount_value' => 'float',
    ];

    public function rewardTier(): BelongsTo
    {
        return $this->belongsTo(RewardTier::class);
    }

    public function diagnosticPackage(): BelongsTo
    {
        return $this->belongsTo(DiagnosticPackage::class, 'diagnostic_package_id');
    }
}
