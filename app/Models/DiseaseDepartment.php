<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseDepartment extends Model
{
    protected $fillable = [
        'department_name',
        'department_image',
        'diseases',
    ];

    protected $casts = [
        'diseases' => 'array',
    ];
    
}
