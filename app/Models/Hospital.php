<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Organization;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Procedure;
use App\Models\LocationMaster;
use App\Models\Speciality;
use App\Models\Pharmacy;
use App\Models\Diagnostic;
use App\Models\PharmacyProducts;
use App\Models\ProcedureBooking;
use App\Models\DoctorBooking;
use App\Models\HospitalReview;

class Hospital extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'address',
        'logo',
        'admin_name',
        'admin_contact',
        'admin_email',
        'admin_address',
        'admin_longitude',
        'admin_latitude',
        'organization_id',
        'location_id',
        'subtitle',
        'about',
        'pharmacy_ids',
        'diagnostic_center_id',
        'status',
        'created_by',
        'updated_by',
        'pincode',
        'city',
        'area',
        'basic_details_completed',
        'location_completed',
        'capacity_completed',
        'medical_completed',
        'contact_completed',
        'onboarding_status',
        'ownership',
        'establishment_type',
        'bed_strength',
        'icu_beds',
        'operating_theatres',
        'ambulance_available',
        'registration_certificate',
        'ownership_proof',
        'accreditation_certificate',
        'fire_safety_certificate',
        'insurance_policy_number',
        'ownership_proof_doc',
        'admin_emergency_contact',
        'state',
        'admin_pincode',
        'basic_details_status',
        'location_status',
        'capacity_status',
        'medical_status',
        'contact_status',
        'comments',
    ];

    protected $casts = [
        'pharmacy_ids' => 'array',
    ];

    public function organization(){

        return $this->belongsTo(Organization::class,'organization_id');

    }

    public function location()
    {
        return $this->belongsTo(LocationMaster::class, 'location_id');
    }

    public function procedures()
    {
        return $this->hasMany(Procedure::class, 'hospital_id');
    }

    public function specialities()
    {
        return $this->hasMany(Speciality::class, 'hospital_id');
    }

    public function doctors()
    {
        return Doctor::whereJsonContains('hospital_ids', $this->id);
    }

    public function assignments()
    {
        return $this->hasMany(DoctorAssignment::class, 'hospital_id');
    }

    public function pharmacies()
    {
        return Pharmacy::whereIn('id', $this->pharmacy_ids ?? [])->get();
    }

    public function diagnosticCenter()
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_center_id');
    }

    public function pharmacyProducts()
    {
        return PharmacyProducts::whereIn('pharmacy_id', $this->pharmacy_ids ?? []);
    }

    public function diagnosticPackages()
    {
        return DiagnosticPackage::whereIn('diagnostic_id', $this->diagnostic_center_id);
    }

    public function procedureBookings()
    {
        return $this->hasMany(ProcedureBooking::class, 'hospital_id');
    }

    public function doctorBookings()
    {
        return $this->hasMany(DoctorBooking::class, 'hospital_id');
    }

    public function hospitalReviews()
    {
        return $this->hasMany(HospitalReview::class, 'hospital_id');
    }
}
