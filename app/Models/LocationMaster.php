<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Hospital;
use App\Models\ContentTargetArea;

class LocationMaster extends Model
{
    use Userstamps;
    protected $fillable = [
        'state',
        'city',
        'area',
        'zipcode',
        'area_code',
        'latitude',
        'longitude',
        'created_by',
        'updated_by',
    ];

    public function hospitals()
    {
        return $this->hasMany(Hospital::class, 'location_id');
    }

    public function cityHospitals()
    {
        return $this->hasMany(Hospital::class, 'city');
    }

    public function areaHospitals()
    {
        return $this->hasMany(Hospital::class, 'area');
    }

    public function contentTargetAreas()
    {
        return $this->hasMany(ContentTargetArea::class, 'location_master_id');
    }
}
