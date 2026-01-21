<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\Pharmacy;

class PharmacyProducts extends Model
{
    use Userstamps;
    protected $fillable = [
        'pharmacy_id',
        'product_name',
        'product_code',
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
        'product_image',
        'product_description',
        'product_status',
        'medicine_master_id',
        'created_by',
        'updated_by',
    ];

    public function pharmacy()
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function medicineMaster()
    {
        return $this->belongsTo(MedicineMaster::class, 'medicine_master_id');
    }
    
}
