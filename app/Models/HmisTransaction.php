<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HmisTransaction extends Model
{
    public const STATUS_RECORDED = 'recorded';

    public const STATUS_PENDING = 'pending';

    public const STATUS_UNMATCHED = 'unmatched';

    public const STATUS_IGNORED = 'ignored_ineligible';

    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'transaction_id',
        'hospital_code',
        'mrn_id',
        'person_id',
        'user_id',
        'payment_type',
        'paid_at',
        'time_slot',
        'is_paid',
        'status',
        'doctor_id',
        'doctor_name',
        'lab_test_name',
        'original_amount',
        'is_membership_applied',
        'membership_id',
        'percentage_discount_applied',
        'discount_amount',
        'is_coins_used',
        'coins_used',
        'coin_discount_amount',
        'paid_amount',
        'coins_earned',
        'coins_reversed',
        'is_refund',
        'refund_of_transaction_id',
        'flags',
        'other_data',
        'raw_payload',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'is_paid' => 'boolean',
        'original_amount' => 'decimal:2',
        'is_membership_applied' => 'boolean',
        'percentage_discount_applied' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_coins_used' => 'boolean',
        'coins_used' => 'integer',
        'coin_discount_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'coins_earned' => 'integer',
        'coins_reversed' => 'integer',
        'is_refund' => 'boolean',
        'flags' => 'array',
        'other_data' => 'array',
        'raw_payload' => 'array',
    ];
}
