<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HmisPatientSaving extends Model
{
    protected $fillable = [
        'person_id',
        'lifetime_discount_total',
        'membership_discount_total',
        'coins_value_total',
        'lifetime_paid_total',
        'transactions_count',
    ];

    protected $casts = [
        'lifetime_discount_total' => 'decimal:2',
        'membership_discount_total' => 'decimal:2',
        'coins_value_total' => 'decimal:2',
        'lifetime_paid_total' => 'decimal:2',
        'transactions_count' => 'integer',
    ];
}
