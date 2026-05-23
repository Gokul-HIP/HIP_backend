<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\Diagnostic;
use App\Models\DiagnosticPackage;
use App\Models\DiagnosticTestBookingStatus;

class DiagnosticTestBooking extends Model
{
    use Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'patient_id',
        'diagnostic_center_id',
        'package_id',
        'test_type',
        'test_items',
        'sample_collection',
        'booking_date',
        'required_time_slots',
        'status',
        'purpose',
    ];

    protected $casts = [
        'test_items' => 'array',
        'required_time_slots' => 'array',
        'booking_date' => 'date',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function diagnosticCenter()
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_center_id');
    }

    public function patient()
    {
        return $this->belongsTo(Persons::class, 'patient_id');
    }

    public function diagnosticPackage()
    {
        return $this->belongsTo(DiagnosticPackage::class, 'package_id');
    }

    public function statuses()
    {
        return $this->hasMany(DiagnosticTestBookingStatus::class, 'diagnostic_test_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(DiagnosticTestBookingStatus::class, 'diagnostic_test_booking_id')->whereNotNull('notes');
    }
}
