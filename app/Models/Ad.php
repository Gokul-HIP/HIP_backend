<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Hospital;

class Ad extends Model
{
    use Userstamps;
    protected $fillable = [
        'title',
        'description',
        'media_type',
        'media_url',
        'hospital_id',
        'redirect_type',
        'redirect_url',
        'priority_type',
        'start_date',
        'end_date',
        'priority',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function placements()
    {
        return $this->hasMany(AdPlacement::class, 'ad_id');
    }

    public function targetAreas()
    {
        return $this->hasMany(AdTargetArea::class, 'ad_id');
    }

    public function impressions()
    {
        return $this->hasMany(AdImpression::class, 'ad_id');
    }
    
    public function clicks()
    {
        return $this->hasMany(AdClick::class, 'ad_id');
    }
}
