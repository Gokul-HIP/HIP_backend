<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    protected $fillable = [
        'referred_by_doctor_id',
        'hospital_id',
        'referred_to_doctor_id',
        'member_name',
        'country_code',
        'phone_number',
        'referral_date',
        'insurance_member_id',
        'medical_notes',
        'status',
        'save',
    ];

    protected $casts = [
        'referral_date' => 'date',
    ];

    public function referredByDoctor()
    {
        return $this->belongsTo(Doctor::class, 'referred_by_doctor_id');
    }

    public function referredToDoctor()
    {
        return $this->belongsTo(Doctor::class, 'referred_to_doctor_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }
}

