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
        'pharmacy_name',
        'pharmacy_id',
        'pharmacy_address',
        'pharmacy_license_number',
        'pharmacy_gst_num',
        'pharmacy_contact_person_name',
        'pharmacy_contact_person_number',
        'pharmacy_contact_person_email',
        'pharmacy_logo',
        'pharmacy_opening_time',
        'pharmacy_closing_time',
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
