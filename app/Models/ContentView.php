<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\ContentModeration;

class ContentView extends Model
{
    protected $fillable = [
        'member_id',
        'content_id',
        'device_id',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function content()
    {
        return $this->belongsTo(ContentModeration::class, 'content_id');
    }
}
