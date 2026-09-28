<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailedQueueJob extends Model
{
    protected $table = 'failed_jobs';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'failed_at' => 'datetime',
        ];
    }
}
