<?php

namespace App\Models;

use App\Models\Concerns\HasBookingPaymentLabel;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;

class DiagnosticTestBooking extends Model
{
    use HasBookingPaymentLabel, Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'patient_id',
        'relationship',
        'branch_id',
        'diagnostic_center_id',
        'package_id',
        'package_type',
        'test_type',
        'test_items',
        'sample_collection',
        'booking_date',
        'required_time_slots',
        'status',
        'purpose',
        'is_coins_applied',
        'coins_used',
        'package_fee',
        'service_charges',
        'total_discount',
        'amount_after_discount',
        'total_amount',
        'is_online_payment',
        'invoice_id',
        'payment_status',
    ];

    protected $casts = [
        'test_items'            => 'array',
        'required_time_slots'   => 'array',
        'booking_date'          => 'date',
        'is_coins_applied'      => 'boolean',
        'coins_used'            => 'integer',
        'package_fee'           => 'decimal:2',
        'service_charges'       => 'decimal:2',
        'total_discount'        => 'decimal:2',
        'amount_after_discount' => 'decimal:2',
        'total_amount'          => 'decimal:2',
        'is_online_payment'     => 'boolean',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function branch()
    {
        return $this->belongsTo(Hospital::class, 'branch_id');
    }

    public function diagnosticCenter()
    {
        return $this->belongsTo(Diagnostic::class, 'diagnostic_center_id');
    }

    public function patient()
    {
        return $this->belongsTo(Persons::class, 'patient_id');
    }

    public function diagnosticPackage()
    {
        return $this->belongsTo(DiagnosticPackage::class, 'package_id');
    }

    public function diseasePackage()
    {
        return $this->belongsTo(DiseasePackage::class, 'package_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function statuses()
    {
        return $this->hasMany(DiagnosticTestBookingStatus::class, 'diagnostic_test_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(DiagnosticTestBookingStatus::class, 'diagnostic_test_booking_id')->whereNotNull('notes');
    }
}
