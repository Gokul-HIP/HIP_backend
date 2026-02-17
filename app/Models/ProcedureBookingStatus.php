<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureBookingStatus extends Model
{
    
    protected $fillable = [
        'procedure_booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'notes_by',
    ];

    public function procedureBooking()
    {
        return $this->belongsTo(ProcedureBooking::class, 'procedure_booking_id');
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
