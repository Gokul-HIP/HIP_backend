<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\WellnessCenters;
use App\Models\WellnessBookingStatus;

class WellnessBooking extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'center_id',
        'booking_date',
        'consultation_type',
        'required_time_slots',
        'status',
        'purpose',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function center()
    {
        return $this->belongsTo(WellnessCenters::class, 'center_id');
    }

    public function statuses()
    {
        return $this->hasMany(WellnessBookingStatus::class, 'wellness_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(WellnessBookingStatus::class, 'wellness_booking_id')->whereNotNull('notes');
    }
}
