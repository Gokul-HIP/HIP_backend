<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\SpecialitiesMaster;

class Speciality extends Model
{
    use Userstamps;
    protected $fillable = [
        'speciality_name',
        'speciality_code',
        'speciality_description',
        'speciality_logo',
        'department_category',
        'status',
        'hospital_id',
        'organization_id',
        'speciality_master_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [

        'qualifications' => 'array',
        'publications' => 'array',
        'achievements' => 'array',
        'speciality' => 'array',
        'assigned_procedure' => 'array',
        'assigned_speciality' => 'array',

    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function specialityMaster()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'speciality_master_id');
    }
    
}
