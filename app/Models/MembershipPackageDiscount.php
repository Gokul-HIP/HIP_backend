<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipPackageDiscount extends Model
{
    protected $fillable = [
        'family_package_id',
        'payment_type',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    public function familyPackage(): BelongsTo
    {
        return $this->belongsTo(FamilyPackage::class, 'family_package_id');
    }
}
