<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmsPayment extends Model
{
    public const RESULT_SETTLED = 'settled';

    public const RESULT_LATE = 'late_settled';

    public const RESULT_FAILED = 'failed_recorded';

    public const RESULT_MISMATCH = 'rejected_amount_mismatch';

    public const RESULT_EXPIRED = 'rejected_expired';

    public const RESULT_CANCELLED = 'rejected_cancelled';

    public const RESULT_DUPLICATE = 'duplicate';

    protected $fillable = [
        'integration_id',
        'hms_booking_row_id',
        'payment_reference',
        'status',
        'amount',
        'method',
        'paid_at',
        'result',
        'payload',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'payload' => 'array',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(HmsBooking::class, 'hms_booking_row_id');
    }
}
