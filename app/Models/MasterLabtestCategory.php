<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class MasterLabtestCategory extends Model
{
    use Userstamps;
    protected $fillable = [
        'category_name',
        'created_by',
        'updated_by',
    ];
}
