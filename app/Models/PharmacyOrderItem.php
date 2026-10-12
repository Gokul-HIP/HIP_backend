<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PharmacyOrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'pharmacy_product_id',
        'medicine_master_id',
        'name',
        'dosage_form',
        'strength',
        'dosage',
        'frequency',
        'duration',
        'when_to_take',
        'special_instruction',
        'qty',
        'unit_price',
        'line_total',
        'is_custom',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
        'is_custom' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PharmacyOrder::class, 'order_id');
    }

    public function pharmacyProduct(): BelongsTo
    {
        return $this->belongsTo(PharmacyProducts::class, 'pharmacy_product_id');
    }

    public function medicineMaster(): BelongsTo
    {
        return $this->belongsTo(MedicineMaster::class, 'medicine_master_id');
    }
}
