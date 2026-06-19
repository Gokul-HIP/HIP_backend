<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\SpecialitiesMaster;
use App\Models\DoctorBooking;
use App\Models\DoctorReview;
use App\Models\DoctorCredential;
use App\Models\DoctorSchedule;
use App\Models\Referral;

class Doctor extends Model
{
    use Userstamps;
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'mobile_number',
        'qualifications',
        'working_since',
        'about_doctor',
        'email',
        'publications',
        'achievements',
        'doctor_image',
        'gender',
        'speciality',
        'hospital_ids',
        'assigned_speciality',
        'assigned_diseases',
        'assigned_procedure',
        'assigned_hospital',
        'assigned_organization',
        'status',
        'organization_id',
        'consultation_fee',
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'hospital_ids'   => 'array',
        'speciality'     => 'array',
        'qualifications' => 'array',
        'publications'   => 'array',
        'achievements'   => 'array',
        'assigned_speciality' => 'array',
        'assigned_diseases'     => 'array',
        'assigned_procedure' => 'array',
        'assigned_hospital' => 'array',
        
    ];

    protected static function booted(): void
    {
        static::creating(function (Doctor $doctor): void {
            if (!$doctor->getKey()) {
                $doctor->{$doctor->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    public function hospitals()
    {
        return Hospital::whereIn('id', $this->hospital_ids ?? [])->get();
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function assignments()
    {
        return $this->hasMany(DoctorAssignment::class, 'doctor_id');
    }

    public function getQualificationNamesAttribute()
    {
        if (empty($this->qualifications)) {
            return '-';
        }

        return MasterQualification::whereIn('id', $this->qualifications)->pluck('name')->join(', ');
        
    }

    public function getSpecialityNamesAttribute()
    {
        if (empty($this->speciality)) {
            return '-';
        }

        return SpecialitiesMaster::whereIn('id', $this->speciality)
            ->pluck('name')
            ->join(', ');
    }

    public function getAssignedDiseaseNamesAttribute(): string
    {
        if (empty($this->assigned_diseases)) {
            return '-';
        }

        return SpecialitiesMaster::query()
            ->whereIn('id', $this->assigned_diseases)
            ->orderBy('name')
            ->get()
            ->flatMap(fn (SpecialitiesMaster $speciality) => collect($speciality->normalizedDiseaseEntries())->pluck('name'))
            ->filter()
            ->unique()
            ->join(', ') ?: '-';
    }

    public function doctorBookings()
    {
        return $this->hasMany(DoctorBooking::class, 'doctor_id');
    }

    public function doctorReviews()
    {
        return $this->hasMany(DoctorReview::class, 'doctor_id');
    }

    public function credential()
    {
        return $this->hasOne(DoctorCredential::class, 'doctor_id');
    }

    public function doctorSchedules()
    {
        return $this->hasMany(DoctorSchedule::class, 'doctor_id');
    }

    public function sentReferrals()
    {
        return $this->hasMany(Referral::class, 'referred_by_doctor_id');
    }

    public function receivedReferrals()
    {
        return $this->hasMany(Referral::class, 'referred_to_doctor_id');
    }
}
