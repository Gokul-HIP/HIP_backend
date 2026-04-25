<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coins extends Model
{
    protected $table = 'coins';

    protected $fillable = [
        'person_id',
        'organization_id',
        'coins',
    ];
}
