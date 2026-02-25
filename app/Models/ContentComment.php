<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\HIPUser;

class ContentComment extends Model
{
    protected $fillable = [
        'member_id',
        'content_id',
        'comment',
        'status',
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
