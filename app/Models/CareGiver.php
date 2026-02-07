<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\WellnessCenters;

class CareGiver extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'gender',
        'category',
        'qualification',
        'working_since',
        'about',
        'wellness_center_id',
        'mobile_number',
        'whatsapp_number',
        'email',
        'image',
        'gallery',
        'address_line_1',
        'address_line_2',
        'city',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'qualification' => 'array',
        'gallery' => 'array',
        'is_active' => 'boolean',
    ];

    public function wellnessCenter()
    {
        return $this->belongsTo(WellnessCenters::class, 'wellness_center_id');
    }
}
