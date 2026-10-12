<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyOrderComment extends Model
{
    protected $fillable = [
        'order_id',
        'author_type',
        'author_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PharmacyOrder::class, 'order_id');
    }
}
