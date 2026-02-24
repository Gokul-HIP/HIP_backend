<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Ad;

class AdPlacement extends Model
{
    use Userstamps;
    protected $fillable = [
        'ad_id',
        'placement_type',
    ];

    public function ad()
    {
        return $this->belongsTo(Ad::class, 'ad_id');
    }
}
