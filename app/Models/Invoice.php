<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Persons;
use App\Models\Transactions;
use App\Models\DoctorBooking;
use Mattiverse\Userstamps\Traits\Userstamps;

class Invoice extends Model
{
    use Userstamps;

    protected $fillable = [
        'primary_person_id',
        'person_id',
        'doctor_booking_id',
        'second_opinion_id',
        'service_types',
        'invoice_details',
        'prescription_img',
        'total_amount',
        'total_gst',
        'amount',
        'service_charges',
        'payment_gateway_charges',
        'discount_price',
        'status',
        'coins_applied',
        'coins_earned',
        'payment_method',
        'is_notified',
        'last_reminder_sent_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'service_types' => 'array',
        'invoice_details' => 'array',
        'total_amount' => 'decimal:2',
        'total_gst' => 'decimal:2',
        'amount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'payment_gateway_charges' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_notified' => 'boolean',
        'last_reminder_sent_at' => 'datetime',
    ];

    protected $enum = [
        'status' => ['pending', 'completed', 'failed', 'refunded', 'cancelled'],
    ];

    public function primaryPerson()
    {
        return $this->belongsTo(Persons::class, 'primary_person_id');
    }

    public function person()
    {
        return $this->belongsTo(Persons::class, 'person_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }

    public function doctorBooking()
    {
        return $this->belongsTo(DoctorBooking::class, 'doctor_booking_id');
    }

    public function secondOpinion()
    {
        return $this->belongsTo(SecondOpinion::class, 'second_opinion_id');
    }
}
