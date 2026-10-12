<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyOrderStatusLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'note',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PharmacyOrder::class, 'order_id');
    }
}
