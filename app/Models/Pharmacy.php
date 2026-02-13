<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Organization;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Hospital;

class Pharmacy extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'pharmacy_id',
        'address',
        'license_number',
        'gst_number',
        'contact_person_name',
        'contact_person_number',
        'contact_person_email',
        'logo',
        'opening_time',
        'closing_time',
        'organization_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function organization(){

        return $this->belongsTo(Organization::class,'organization_id');

    }

    public function hospitals()
    {
        return $this->hasMany(Hospital::class, 'pharmacy_ids');
    }

}
