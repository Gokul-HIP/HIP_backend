<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorCredential extends Model
{
    protected $fillable = [
        'doctor_id',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}

