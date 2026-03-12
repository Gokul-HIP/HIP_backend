<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class DoctorSchedule extends Model
{
    use Userstamps;

    protected $fillable = [
        'doctor_id',
        'organization_id',
        'schedule_date',
        'time_slots',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'schedule_date' => 'date',
        'time_slots' => 'array',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }
}
