<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Ad;
use App\Models\HIPUser;
use App\Models\AdPlacement;

class AdImpression extends Model
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

    public function placement()
    {
        return $this->belongsTo(AdPlacement::class, 'placement_id');
    }
}
