<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'about',
        'symptoms',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'symptoms' => 'array',
    ];
}
