<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DatabaseQueueJob extends Model
{
    protected $table = 'jobs';

    public $timestamps = false;

    protected $guarded = [];

    public function availableAt(): ?Carbon
    {
        return $this->available_at ? Carbon::createFromTimestamp((int) $this->available_at) : null;
    }

    public function reservedAt(): ?Carbon
    {
        return $this->reserved_at ? Carbon::createFromTimestamp((int) $this->reserved_at) : null;
    }

    public function queuedAt(): ?Carbon
    {
        return $this->created_at ? Carbon::createFromTimestamp((int) $this->created_at) : null;
    }

    public function isReserved(): bool
    {
        return $this->reserved_at !== null;
    }
}
