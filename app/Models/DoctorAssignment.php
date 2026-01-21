<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class DoctorAssignment extends Model
{
    use Userstamps;
    protected $fillable = [
        'doctor_id',
        'hospital_id',
        'day',
        'date',
        'time_slots',
        'procedure_ids',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'time_slots' => 'array',
        'procedure_ids' => 'array',
        'date' => 'date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function procedures()
    {
        return Procedure::whereIn('id', $this->procedure_ids ?? [])->get();
    }
}
