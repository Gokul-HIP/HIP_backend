<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Products;

class ProductBenefit extends Model
{
    use Userstamps;
    protected $fillable = [
        'product_id',
        'title',
        'icon',
        'display_order',
    ];

    public function product()
    {
        return $this->belongsTo(Products::class, 'product_id');
    }
}
