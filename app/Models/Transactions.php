<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Invoice;
use Mattiverse\Userstamps\Traits\Userstamps;

class Transactions extends Model
{
    use Userstamps;
    protected $fillable = [
        'invoice_id',
        'service_types',
        'invoice_details',
        'transaction_amount',
        'service_charges',
        'payment_gateway_charges',
        'discount_amount',
        'total_gst',
        'total_amount',
        'status',
        'payment_method',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'service_types' => 'array',
        'invoice_details' => 'array',
    ];

    protected $enum = [
        'status' => ['pending', 'completed', 'failed', 'refunded', 'cancelled'],
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
