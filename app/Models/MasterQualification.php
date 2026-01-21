<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class MasterQualification extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'description',
        'created_by',
        'updated_by',
    ];

}
