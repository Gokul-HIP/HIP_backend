<?php

namespace App\Models;

use App\Models\Concerns\HasBookingPaymentLabel;
use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Doctor;
use App\Models\Persons;
use App\Models\SpecialitiesMaster;
use App\Models\DoctorBookingStatus;
use App\Models\Invoice;

class DoctorBooking extends Model
{
    use HasBookingPaymentLabel, Userstamps;

    protected $fillable = [
        'name',
        'mobile_number',
        'member_id',
        'branch_id',
        'hospital_id',
        'doctor_id',
        'booking_date',
        'consultation_type',
        'appointment_type',
        'department_id',
        'patient_id',
        'relationship',
        'required_time_slots',
        'status',
        'purpose',
        'reason_of_visit',
        'message',
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
        'is_follow_up',
        'follow_up_reason',
        'clinical_notes',
        'send_notification_reminder',
        'appointment_status',
        'online_consultation_link',
    ];

    protected $casts = [
        'required_time_slots' => 'array',
        'booking_date' => 'date',
        'is_coins_applied' => 'boolean',
        'coins_used' => 'integer',
        'total_amount' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'service_charges' => 'decimal:2',
        'consultation_fee' => 'decimal:2',
        'amount_after_discount' => 'decimal:2',
        'is_online_payment' => 'boolean',
        'is_follow_up' => 'boolean',
        'send_notification_reminder' => 'boolean',
    ];

    public const APPOINTMENT_STATUS_NEW = 'new_scheduled';
    public const APPOINTMENT_STATUS_CHECKED_IN = 'checked_in';
    public const APPOINTMENT_STATUS_COMPLETED = 'completed';
    public const APPOINTMENT_STATUS_CANCELLED = 'cancelled';

    public static function appointmentStatusOptions(): array
    {
        return [
            self::APPOINTMENT_STATUS_NEW => 'New/Scheduled',
            self::APPOINTMENT_STATUS_CHECKED_IN => 'Checked-In',
            self::APPOINTMENT_STATUS_COMPLETED => 'Completed',
            self::APPOINTMENT_STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public function appointmentStatusLabel(): string
    {
        $status = $this->appointment_status ?: self::APPOINTMENT_STATUS_NEW;

        return self::appointmentStatusOptions()[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public function appointmentStatusBadgeClass(): string
    {
        return match ($this->appointment_status ?: self::APPOINTMENT_STATUS_NEW) {
            self::APPOINTMENT_STATUS_CHECKED_IN => 'status-checked-in',
            self::APPOINTMENT_STATUS_COMPLETED => 'status-completed',
            self::APPOINTMENT_STATUS_CANCELLED => 'status-cancelled',
            default => 'status-upcoming',
        };
    }

    public function scopeOnlineConsultation($query)
    {
        return $query->where(function ($builder) {
            $builder
                ->whereRaw('LOWER(COALESCE(consultation_type, "")) LIKE ?', ['%online%'])
                ->orWhereRaw('LOWER(COALESCE(consultation_type, "")) LIKE ?', ['%video%'])
                ->orWhereRaw('LOWER(COALESCE(appointment_type, "")) LIKE ?', ['%online%'])
                ->orWhereRaw('LOWER(COALESCE(appointment_type, "")) LIKE ?', ['%video%']);
        });
    }

    public function isOnlineConsultation(): bool
    {
        $type = strtolower(trim((string) ($this->consultation_type ?: $this->appointment_type ?: '')));

        return str_contains($type, 'online') || str_contains($type, 'video');
    }

    public function onlineCallStatusLabel(): string
    {
        return match ($this->appointment_status ?: self::APPOINTMENT_STATUS_NEW) {
            self::APPOINTMENT_STATUS_CHECKED_IN => 'Started',
            self::APPOINTMENT_STATUS_COMPLETED => 'Completed',
            self::APPOINTMENT_STATUS_CANCELLED => 'Cancelled',
            default => 'Scheduled',
        };
    }

    public function onlineCallStatusClass(): string
    {
        return match ($this->appointment_status ?: self::APPOINTMENT_STATUS_NEW) {
            self::APPOINTMENT_STATUS_CHECKED_IN => 'status-started',
            self::APPOINTMENT_STATUS_COMPLETED => 'status-completed-call',
            self::APPOINTMENT_STATUS_CANCELLED => 'status-cancelled-call',
            default => 'status-scheduled',
        };
    }

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function hospital()
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function branch()
    {
        return $this->belongsTo(Hospital::class, 'branch_id');
    }

    public function department()
    {
        return $this->belongsTo(SpecialitiesMaster::class, 'department_id');
    }

    public function patient()
    {
        return $this->belongsTo(Persons::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function statuses()
    {
        return $this->hasMany(DoctorBookingStatus::class, 'doctor_booking_id');
    }

    public function notes()
    {
        return $this->hasMany(DoctorBookingStatus::class, 'doctor_booking_id')->whereNotNull('notes');
    }

}
