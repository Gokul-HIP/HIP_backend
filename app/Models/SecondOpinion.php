<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Mattiverse\Userstamps\Traits\Userstamps;

class SecondOpinion extends Model
{
    use Userstamps;

    protected $fillable = [
        'member_id',
        'patient_id',
        'patient_name',
        'diagnosis',
        'treatment',
        'question_for_doctor',
        'document_ids',
        'branch_id',
        'speciality_id',
        'doctor_id',
        'mode_of_consultation',
        'preferred_date',
        'preferred_time_slots',
        'status',
        'relationship',
        'is_coins_applied',
        'coins_used',
        'total_amount',
        'total_discount',
        'service_charges',
        'consultation_fee',
        'amount_after_discount',
        'is_online_payment',
        'invoice_id',
        'payment_status',
    ];

    protected $casts = [
        'document_ids'           => 'array',
        'preferred_time_slots'   => 'array',
        'preferred_date'         => 'date',
        'is_coins_applied'       => 'boolean',
        'coins_used'             => 'integer',
        'total_amount'           => 'decimal:2',
        'total_discount'         => 'decimal:2',
        'service_charges'        => 'decimal:2',
        'consultation_fee'       => 'decimal:2',
        'amount_after_discount'  => 'decimal:2',
        'is_online_payment'      => 'boolean',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'branch_id');
    }

    public function speciality(): BelongsTo
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'speciality_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Documents referenced by document_ids JSON array.
     */
    public function documentRecords(): Collection
    {
        $ids = array_values(array_filter(array_map('intval', $this->document_ids ?? [])));

        if ($ids === []) {
            return collect();
        }

        return Document::query()->whereIn('id', $ids)->get();
    }
}
