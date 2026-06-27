<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Pharmacy;
use App\Models\ProductBenefit;

class Products extends Model
{
    use Userstamps;
    protected $fillable = [
        'pharmacy_id',
        'product_name',
        'product_code',
        'category',
        'brand_name',
        'mrp',
        'selling_price',
        'discount',
        'stock_quantity',
        'in_stock',
        'description',
        'images',
        'rating',
        'review_count',
        'status',
    ];

    protected $casts = [
        'images' => 'array',
        'status' => 'boolean',
        'in_stock' => 'boolean',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function productBenefits()
    {
        return $this->hasMany(ProductBenefit::class, 'product_id');
    }
}
