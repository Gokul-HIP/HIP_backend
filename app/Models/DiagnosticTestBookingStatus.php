<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class DiagnosticTestBookingStatus extends Model
{
    use Userstamps;

    protected $fillable = [
        'diagnostic_test_booking_id',
        'from_status',
        'to_status',
        'changed_by',
        'notes',
        'notes_by',
    ];

    public function diagnosticTestBooking()
    {
        return $this->belongsTo(DiagnosticTestBooking::class, 'diagnostic_test_booking_id');
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
