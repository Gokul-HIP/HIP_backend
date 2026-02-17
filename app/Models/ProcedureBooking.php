<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class ProcedureBooking extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'message',
        'procedure_id',
        'hospital_id',
        'member_id',
        'booking_date',
        'required_time_slots',
        'status'
    ];

    protected $casts = [
        'required_time_slots' => 'array',
        'booking_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function statuses()
    {
        return $this->hasMany(ProcedureBookingStatus::class, 'procedure_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(ProcedureBookingStatus::class, 'procedure_booking_id')->whereNotNull('notes');
    }

    public function procedure()
    {
        return $this->belongsTo(Procedure::class, 'procedure_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

}
