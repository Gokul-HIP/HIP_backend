<?php

namespace App\Modules\Automation\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

class AppointmentReminderSchedule extends Model
{
    protected $fillable = [
        'identity', 'booking_type', 'booking_id', 'workflow_id', 'workflow_version_id',
        'hospital_id', 'organization_id', 'appointment_at', 'timezone', 'configured_timing', 'stage',
        'scheduled_at', 'status',
    ];

    protected $casts = [
    ];

    public function getAppointmentAtAttribute(mixed $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value, 'UTC');
    }

    public function setAppointmentAtAttribute(mixed $value): void
    {
        $this->attributes['appointment_at'] = $value === null ? null : CarbonImmutable::parse($value)->utc()->format('Y-m-d H:i:s');
    }

    public function getScheduledAtAttribute(mixed $value): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value, 'UTC');
    }

    public function setScheduledAtAttribute(mixed $value): void
    {
        $this->attributes['scheduled_at'] = $value === null ? null : CarbonImmutable::parse($value)->utc()->format('Y-m-d H:i:s');
    }
}
