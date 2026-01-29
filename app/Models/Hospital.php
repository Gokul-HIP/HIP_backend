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

class Hospital extends Model
{
    use Userstamps;
    protected $fillable = [
        'hospital_name',
        'hospital_address',
        'hospital_logo',
        'hospital_admin_name',
        'hospital_admin_contact',
        'hospital_admin_email',
        'hospital_admin_address',
        'hospital_admin_longitude',
        'hospital_admin_latitude',
        'organization_id',
        'location_id',
        'hospital_subtitle',
        'hospital_about',
        'pharmacy_ids',
        'diagnostic_center_id',
        'status',
        'created_by',
        'updated_by',
        
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
}
