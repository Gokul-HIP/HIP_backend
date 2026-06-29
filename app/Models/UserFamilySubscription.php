<?php

namespace App\Models;

use App\Services\FamilyPackageService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserFamilySubscription extends Model
{
    protected $fillable = [
        'hip_user_id',
        'family_package_id',
        'covered_member_ids',
        'start_date',
        'end_date',
        'status',
        'payment_status',
        'payment_mode',
        'activated_at',
        'created_by',
        'invoice_id',
        'amount_paid',
        'auto_renew',
        'cancelled_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'covered_member_ids' => 'array',
        'amount_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function familyPackage(): BelongsTo
    {
        return $this->belongsTo(FamilyPackage::class, 'family_package_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'created_by');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(SubscriptionUsageLog::class, 'user_family_subscription_id');
    }

    public function userRewards(): HasMany
    {
        return $this->hasMany(UserReward::class, 'entity_id')
            ->where('entity_type', UserReward::ENTITY_TYPE_FAMILY_PACKAGE);
    }

    public function isActive(): bool
    {
        if ($this->status === 'active' && $this->end_date && $this->end_date->toDateString() < now()->toDateString()) {
            app(FamilyPackageService::class)->expireSubscriptionIfStale($this);
        }

        return $this->status === 'active'
            && $this->end_date
            && $this->end_date->toDateString() >= now()->toDateString();
    }

    public function getConsultationUsedAttribute(): int
    {
        return $this->usageLogs()->where('usage_type', 'consultation')->count();
    }

    public function getLabTestUsedAttribute(): int
    {
        return $this->usageLogs()->where('usage_type', 'lab_test')->count();
    }

    public function getConsultationRemainingAttribute(): int
    {
        $max = (int) ($this->familyPackage?->max_consultations ?? 0);

        if ($max === 0) {
            return PHP_INT_MAX;
        }

        return max(0, $max - $this->consultation_used);
    }

    public function getLabTestRemainingAttribute(): int
    {
        $max = (int) ($this->familyPackage?->max_lab_tests ?? 0);

        if ($max === 0) {
            return PHP_INT_MAX;
        }

        return max(0, $max - $this->lab_test_used);
    }
}
