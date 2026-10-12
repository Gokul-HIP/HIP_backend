<?php

namespace App\Models;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use App\Policies\PharmacyOrderPolicy;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mattiverse\Userstamps\Traits\Userstamps;

#[UsePolicy(PharmacyOrderPolicy::class)]
class PharmacyOrder extends Model
{
    use SoftDeletes;
    use Userstamps;

    public const PRESCRIPTION_DOC_TYPE = 'pharmacy_prescription';

    protected $fillable = [
        'order_number',
        'hip_user_id',
        'person_id',
        'hospital_id',
        'pharmacy_id',
        'prescription_document_id',
        'status',
        'payment_status',
        'subtotal',
        'discount',
        'delivery_fee',
        'tax',
        'service_charge',
        'gateway_charge',
        'total',
        'invoice_id',
        'delivery_name',
        'delivery_phone',
        'delivery_address',
        'delivery_partner',
        'rider_name',
        'tracking_ref',
        'expected_delivery_at',
        'assigned_pharmacist_id',
        'reviewed_by',
        'cancelled_by',
        'cancel_reason',
        'deleted_by',
        'paid_at',
        'processing_at',
        'ready_at',
        'in_review_at',
        'dispatched_at',
        'out_for_delivery_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'status' => PharmacyOrderStatus::class,
        'payment_status' => PharmacyPaymentStatus::class,
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'gateway_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'processing_at' => 'datetime',
        'ready_at' => 'datetime',
        'in_review_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'expected_delivery_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function hipUser(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'hip_user_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Persons::class, 'person_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function pharmacy(): BelongsTo
    {
        return $this->belongsTo(Pharmacy::class, 'pharmacy_id');
    }

    public function prescriptionDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'prescription_document_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function assignedPharmacist(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'assigned_pharmacist_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(HIPUser::class, 'reviewed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PharmacyOrderItem::class, 'order_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PharmacyOrderComment::class, 'order_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(PharmacyOrderStatusLog::class, 'order_id');
    }

    /**
     * @param  array<int, string>  $personIds
     */
    public function scopeVisibleToPatient(Builder $query, HIPUser $user, array $personIds): Builder
    {
        return $query->where(function (Builder $inner) use ($user, $personIds) {
            $inner->where('hip_user_id', $user->id);

            if ($personIds !== []) {
                $inner->orWhereIn('person_id', $personIds);
            }
        });
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->status instanceof PharmacyOrderStatus
            ? $this->status
            : PharmacyOrderStatus::tryFrom((string) $this->status);

        return $status?->label() ?? (string) $this->status;
    }
}
