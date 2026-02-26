<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Hospital;
use App\Models\Organization;
use App\Models\Speciality;
use App\Models\ProcedureMaster;
use App\Models\ProcedureBooking;

class Procedure extends Model
{
    protected $fillable = [
        'procedure_name',
        'speciality_id',
        'procedure_master_id',
        'assign_doctor',
        'description',
        'estimated_time',
        'cost',
        'procedure_code',
        'status',
        'hospital_id',
        'organization_id',
        'image',
        'recovery_time',
        'success_rate',
        'hospitalization_days',
        'discount',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }

    public function procedureMaster()
    {
        return $this->belongsTo(ProcedureMaster::class);
    }

    public function procedureBookings()
    {
        return $this->hasMany(ProcedureBooking::class, 'procedure_id');
    }

}
