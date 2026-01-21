<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\DiagnosticLabTest;
use App\Models\LocationMaster;
use App\Models\Hospital;

class Diagnostic extends Model
{
    use Userstamps;
    protected $fillable = [

        'diagnostic_center_name',
        'diagnostic_center_address',
        'diagnostic_logo',
        'diagnostic_contact_person_name',
        'diagnostic_contact_person_number',
        'diagnostic_contact_person_email',
        'diagnostic_contcat_person_address',
        'diagnostic_contact_person_longitude',
        'diagnostic_contact_person_latitude',
        'organization_id',
        'location_id',
        'status',
        'created_by',
        'updated_by',

    ];

    public function organization(){

        return $this->belongsTo(Organization::class,'organization_id');

    }

    public function location()
    {
        return $this->belongsTo(LocationMaster::class, 'location_id');
    }

    public function labTests()
    {
        return $this->hasMany(DiagnosticLabTest::class, 'diagnostic_id');
    }

    public function hospitals()
    {
        return $this->hasMany(Hospital::class, 'diagnostic_center_id');
    }

}
