<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HIPUser;
use App\Models\Doctor;

class DoctorReview extends Model
{
    protected $fillable = [
        'member_id',
        'doctor_id',
        'review',
        'quick_tags',
        'rating',
        'status',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function displayComment(): ?string
    {
        return filled($this->review) ? $this->review : ($this->quick_tags ?: null);
    }
}
