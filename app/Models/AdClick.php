<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class AdClick extends Model
{
    use Userstamps;
    protected $fillable = [
        'ad_id',
        'member_id',
        'device_id',
        'ip_address',
        'placement_id',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }
}
