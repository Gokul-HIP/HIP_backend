<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Ad;
use App\Models\LocationMaster;
class AdTargetArea extends Model
{
    use Userstamps;
    protected $fillable = [
        'ad_id',
        'location_master_id',
    ];

    public function locationMaster()
    {
        return $this->belongsTo(LocationMaster::class, 'location_master_id');
    }

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
