<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HIPUser;
use App\Models\Hospital;

class HospitalReview extends Model
{
    protected $fillable = [
        'member_id',
        'hospital_id',
        'review',
        'rating',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }
}
