<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;

class StemCellBooking extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
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

    public function statuses()
    {
        return $this->hasMany(StemCellBookingStatus::class, 'stem_cell_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(StemCellBookingStatus::class, 'stem_cell_booking_id')->whereNotNull('notes');
    }
}
