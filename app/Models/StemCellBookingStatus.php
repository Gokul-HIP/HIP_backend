<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\StemCellBooking;

class StemCellBookingStatus extends Model
{
    use Userstamps;

    protected $fillable = [
        'stem_cell_booking_id',
        'notes',
        'notes_by',
    ];

    public function stemCellBooking()
    {
        return $this->belongsTo(StemCellBooking::class, 'stem_cell_booking_id');
    }

    public function notesBy()
    {
        return $this->belongsTo(HIPUser::class, 'notes_by');
    }
}
