<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterWellnessCategories extends Model
{
    protected $fillable = [
        'parent_category',
        'submenus',
    ];

    protected $casts = [
        'submenus' => 'array',
    ];
}
