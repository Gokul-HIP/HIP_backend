<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class Prescription extends Model
{
    use Userstamps;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SENT = 'sent';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'member_id',
        'hospital_id',
        'doctor_booking_id',
        'follow_up_booking_id',
        'medications',
        'lab_tests',
        'document_ids',
        'clinical_notes',
        'follow_up_date',
        'vitals',
        'diagnosis',
        'status',
    ];

    protected $casts = [
        'medications' => 'array',
        'lab_tests' => 'array',
        'document_ids' => 'array',
        'follow_up_date' => 'date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function patient()
    {
        return $this->belongsTo(Persons::class, 'patient_id');
    }

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function doctorBooking()
    {
        return $this->belongsTo(DoctorBooking::class, 'doctor_booking_id');
    }

    public function followUpBooking()
    {
        return $this->belongsTo(DoctorBooking::class, 'follow_up_booking_id');
    }

    public function documents()
    {
        $ids = collect($this->document_ids ?? [])->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return Document::query()->whereIn('id', $ids)->get();
    }
}
