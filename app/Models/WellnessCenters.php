<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class WellnessCenters extends Model
{
    use Userstamps;
    protected $fillable = [
        'centre_type',
        'operating_mode',
        'centre_name',
        'age_group_served',
        'description',
        'languages_supported',
        'target_audience',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'centre_website',
        'centre_social_links',
        'contact_person_name',
        'contact_person_mobile',
        'contact_person_email',
        'business_registration_type',
        'gst_number',
        'registration_certificate',
        'ownership_proof',
        'accreditation_certificate',
        'fire_safety_certificate',
        'insurance_policy_number',
        'ownership_proof_doc',
        'comments',
        'status',
        'created_by',
        'updated_by',
    ];
}
