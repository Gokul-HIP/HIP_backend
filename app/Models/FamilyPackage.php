<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FamilyPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'max_members',
        'max_consultations',
        'max_lab_tests',
        'max_hip_coins',
        'branch_ids',
        'benefits',
        'terms_conditions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'branch_ids' => 'array',
        'benefits' => 'array',
        'terms_conditions' => 'array',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
        'max_members' => 'integer',
        'max_consultations' => 'integer',
        'max_lab_tests' => 'integer',
        'max_hip_coins' => 'integer',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserFamilySubscription::class, 'family_package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
