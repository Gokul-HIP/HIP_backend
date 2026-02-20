<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\SpecialitiesMaster;
use App\Models\LocationMaster;
use App\Models\Hospital;
use App\Models\Organization;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Casts\Attribute;

class ContentModeration extends Model
{
    use Userstamps;
    protected $fillable = [
        'title',
        'description',
        'media_file',
        'speciality_id',
        'category',
        'area_ids',
        'hospital_id',
        'organization_id',
        'doctor_id',
        'status',
        'is_published',
        'schedule_time_data',
        'created_by',
        'updated_by',
        'like_count',
        'view_count',
        'comment_count',
    ];

    protected $casts = [
        'area_ids' => 'array',
        'schedule_time_data' => 'array',
        'is_published' => 'boolean',
    ];

    public function speciality()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'speciality_id');
    }
    
    public function area()
    {
        return Attribute::make(
            get: function () {
                return LocationMaster::whereIn('id', $this->area_ids ?? [])->get();
            }
        );
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
