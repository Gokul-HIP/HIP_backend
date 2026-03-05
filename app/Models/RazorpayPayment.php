<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RazorpayPayment extends Model
{
    protected $fillable = [
        'invoice_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'payment_method',
        'bank',
        'wallet',
        'vpa',
        'card_last4',
        'card_network',
        'amount_paid',
        'razorpay_fee',
        'razorpay_tax',
        'currency',
        'payment_status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'razorpay_fee' => 'decimal:2',
        'razorpay_tax' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
