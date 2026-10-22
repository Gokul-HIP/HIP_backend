<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiseaseChatbotBooking extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUND_REQUIRED = 'refund_required';

    protected $table = 'disease_chatbot_bookings';

    protected $fillable = [
        'session_id',
        'doctor_booking_id',
        'invoice_id',
        'status',
        'expires_at',
        'refund_notified_at',
    ];

    protected $casts = [
        'doctor_booking_id' => 'integer',
        'invoice_id' => 'integer',
        'expires_at' => 'datetime',
        'refund_notified_at' => 'datetime',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(DiseaseChatbotSession::class, 'session_id');
    }

    public function doctorBooking(): BelongsTo
    {
        return $this->belongsTo(DoctorBooking::class, 'doctor_booking_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
