<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WellnessCenters extends Model
{
    protected $table = 'wellness_centres';
    
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
        'contact_person_name',
        'contact_person_mobile',
        'contact_person_email',
        'business_registration_type',
        'gst_number',
        'registration_certificate',
        'ownership_proof',
        'accreditation_certificate',
        'fire_safety_certificate',
        'insurance_coverage',
        'status',
        'centre_instagram_links',
        'centre_facebook_links',
        'centre_linkedin_links',
        'centre_twitter_links',
        'centre_youtube_links',
    ];

    protected $casts = [
        'insurance_coverage' => 'array',
    ];

}