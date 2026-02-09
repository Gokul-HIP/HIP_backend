<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\CaregiverBooking;

class CaregiverBookingStatus extends Model
{
    use Userstamps;

    protected $fillable = [
        'caregiver_booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'notes_by',
    ];

    public function caregiverBooking()
    {
        return $this->belongsTo(CaregiverBooking::class, 'caregiver_booking_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(HIPUser::class, 'changed_by');
    }

    public function notesBy()
    {
        return $this->belongsTo(HIPUser::class, 'notes_by');
    }
}
