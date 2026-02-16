<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HIPUser;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id', 
        'fcm_token', 
        'device_type',
        'device_id'
    ];

    public function user()
    {
        return $this->belongsTo(HIPUser::class);
    }
}
