<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiseaseDepartment extends Model
{
    protected $fillable = [
        'department_name',
        'department_image',
        'diseases',
        'is_active',
    ];

    protected $casts = [
        'diseases' => 'array',
        'is_active' => 'boolean',
    ];
    
}
