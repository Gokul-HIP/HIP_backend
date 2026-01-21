<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use Illuminate\Support\Facades\Storage;

class MedicineMaster extends Model
{
    use Userstamps;
    protected $fillable = [
        'name',
        'code',
        'category',
        'brand_name',
        'dosage_form',
        'strength',
        'pack_size',
        'mrp',
        'selling_price',
        'discount',
        'stock_quantity',
        'expiry_date',
        'batch_number',
        'prescription_required',
        'image',
        'description',
        'status',
        'created_by',
        'updated_by',
    ];

    protected static function booted()
    {
        static::deleting(function ($record) {
            if ($record->image && Storage::disk('public')->exists($record->image)) {
                Storage::disk('public')->delete($record->image);
            }
        });
    }

}
