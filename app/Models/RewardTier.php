<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class RewardTier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'min_coins',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'min_coins' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function config(): HasOne
    {
        return $this->hasOne(RewardTierConfig::class);
    }

    public function packageDiscounts(): HasMany
    {
        return $this->hasMany(RewardTierPackageDiscount::class);
    }

    public function userRewardProgress(): HasMany
    {
        return $this->hasMany(UserRewardProgress::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
