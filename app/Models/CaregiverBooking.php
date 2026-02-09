<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\Caregiver;
use App\Models\WellnessCenters;

class CaregiverBooking extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'caregiver_id',
        'wellness_center_id',
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

    public function caregiver()
    {
        return $this->belongsTo(Caregiver::class, 'caregiver_id');
    }

    public function wellnessCenter()
    {
        return $this->belongsTo(WellnessCenters::class, 'wellness_center_id');
    }

    public function statuses()
    {
        return $this->hasMany(CaregiverBookingStatus::class, 'caregiver_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(CaregiverBookingStatus::class, 'caregiver_booking_id')->whereNotNull('notes');
    }
}
