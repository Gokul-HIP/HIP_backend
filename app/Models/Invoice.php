<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Persons;
use App\Models\HIPUser;
use App\Models\Transactions;
use App\Models\DoctorBooking;
use App\Models\DiagnosticTestBooking;
use Mattiverse\Userstamps\Traits\Userstamps;

class Invoice extends Model
{
    use Userstamps;

    protected $fillable = [
        'primary_person_id',
        'person_id',
        'doctor_booking_id',
        'second_opinion_id',
        'diagnostic_test_booking_id',
        'service_types',
        'invoice_details',
        'prescription_img',
        'total_amount',
        'total_gst',
        'amount',
        'service_charges',
        'payment_gateway_charges',
        'discount_price',
        'status',
        'coins_applied',
        'coins_earned',
        'payment_method',
        'is_notified',
        'is_in_patient',
        'last_reminder_sent_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'service_types' => 'array',
        'invoice_details' => 'array',
        'total_amount' => 'decimal:2',
        'total_gst' => 'decimal:2',
        'amount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'payment_gateway_charges' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'is_notified' => 'boolean',
        'is_in_patient' => 'boolean',
        'last_reminder_sent_at' => 'datetime',
    ];

    protected $enum = [
        'status' => ['pending', 'completed', 'failed', 'refunded', 'cancelled'],
    ];

    public function primaryPerson()
    {
        return $this->belongsTo(Persons::class, 'primary_person_id');
    }

    public function person()
    {
        return $this->belongsTo(Persons::class, 'person_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transactions::class);
    }

    public function doctorBooking()
    {
        return $this->belongsTo(DoctorBooking::class, 'doctor_booking_id');
    }

    public function secondOpinion()
    {
        return $this->belongsTo(SecondOpinion::class, 'second_opinion_id');
    }

    public function diagnosticTestBooking()
    {
        return $this->belongsTo(DiagnosticTestBooking::class, 'diagnostic_test_booking_id');
    }

    public function creator()
    {
        return $this->belongsTo(HIPUser::class, 'created_by');
    }

    /**
     * Invoices tied to mobile app bookings (doctor, second opinion, diagnostic).
     */
    public function isAppBookingInvoice(): bool
    {
        return ! empty($this->doctor_booking_id)
            || ! empty($this->second_opinion_id)
            || ! empty($this->diagnostic_test_booking_id);
    }

    /**
     * @return array{type: 'app'|'admin', label: string, creator: ?string}
     */
    public function createdInfo(): array
    {
        if ($this->isAppBookingInvoice()) {
            return [
                'type' => 'app',
                'label' => 'App Invoice',
                'creator' => null,
            ];
        }

        $creator = $this->relationLoaded('creator')
            ? $this->creator
            : ($this->created_by ? HIPUser::find($this->created_by) : null);

        $name = $creator
            ? trim(($creator->first_name ?? '') . ' ' . ($creator->last_name ?? ''))
            : '';

        if ($name === '' && $creator) {
            $name = (string) ($creator->email ?? '');
        }

        return [
            'type' => 'admin',
            'label' => 'Admin',
            'creator' => $name !== '' ? $name : null,
        ];
    }

    /**
     * Staff-created invoices at hospital OR app booking invoices for that hospital.
     */
    public function scopeForHospital(Builder $query, ?string $hospitalId): Builder
    {
        if (empty($hospitalId)) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where(function (Builder $q) use ($hospitalId) {
            $q->whereHas('creator', fn (Builder $c) => $c->where('hospital_id', $hospitalId))
                ->orWhereHas('doctorBooking', fn (Builder $b) => $b->where('hospital_id', $hospitalId))
                ->orWhereHas('secondOpinion', fn (Builder $b) => $b->where('branch_id', $hospitalId))
                ->orWhereHas('diagnosticTestBooking', fn (Builder $b) => $b->where('branch_id', $hospitalId));
        });
    }
}
