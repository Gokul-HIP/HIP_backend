<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\LocationMaster;
use App\Models\ContentModeration;

class ContentTargetArea extends Model
{
    protected $fillable = [
        'content_id',
        'location_master_id',
    ];

    public function locationMaster()
    {
        return $this->belongsTo(LocationMaster::class, 'location_master_id');
    }

    public function content()
    {
        return $this->belongsTo(ContentModeration::class, 'content_id');
    }
}
