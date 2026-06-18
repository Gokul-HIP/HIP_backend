<?php

namespace App\Models;

use App\Models\Concerns\HasBookingPaymentLabel;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Persons;
use App\Models\SpecialitiesMaster;
use App\Models\DoctorBookingStatus;
use App\Models\Invoice;

class DoctorBooking extends Model
{
    use HasBookingPaymentLabel, Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'branch_id',
        'hospital_id',
        'doctor_id',
        'booking_date',
        'consultation_type',
        'appointment_type',
        'department_id',
        'patient_id',
        'relationship',
        'required_time_slots',
        'status',
        'purpose',
        'reason_of_visit',
        'message',
        'is_coins_applied',
        'coins_used',
        'total_amount',
        'total_discount',
        'service_charges',
        'consultation_fee',
        'amount_after_discount',
        'is_online_payment',
        'invoice_id',
        'payment_status',
        'is_follow_up'
    ];

    protected $casts = [
        'required_time_slots' => 'array',
        'booking_date' => 'date',
        'is_coins_applied' => 'boolean',
        'coins_used' => 'integer',
        'total_amount' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'consultation_fee' => 'decimal:2',
        'amount_after_discount' => 'decimal:2',
        'is_online_payment' => 'boolean',
        'is_follow_up' => 'boolean'
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function branch()
    {
        return $this->belongsTo(Hospital::class, 'branch_id');
    }

    public function department()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'department_id');
    }

    public function patient()
    {
        return $this->belongsTo(Persons::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function statuses()
    {
        return $this->hasMany(DoctorBookingStatus::class, 'doctor_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(DoctorBookingStatus::class, 'doctor_booking_id')->whereNotNull('notes');
    }

}
