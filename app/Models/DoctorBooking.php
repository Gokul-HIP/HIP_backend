<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\DoctorBookingStatus;

class DoctorBooking extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'hospital_id',
        'doctor_id',
        'booking_date',
        'required_time_slots',
        'status',
        'purpose',
    ];

    protected $casts = [
        'required_time_slots' => 'array',
        'booking_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
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
