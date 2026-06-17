<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HowToEarnContent extends Model
{
    public const TYPE_HEALTH_PACKAGE = 'health_package';

    public const TYPE_BOOKING_APPOINTMENT = 'booking_appointment';

    public const TYPE_SECOND_OPINION = 'second_opinion';

    public const TYPE_FAMILY_PLAN = 'family_plan';

    public const TYPES = [
        self::TYPE_HEALTH_PACKAGE => 'Health Package',
        self::TYPE_BOOKING_APPOINTMENT => 'Booking Appointment',
        self::TYPE_SECOND_OPINION => 'Second Opinion',
        self::TYPE_FAMILY_PLAN => 'Family Plan',
    ];

    protected $fillable = [
        'type',
        'how_to_earn',
        'terms_conditions',
        'is_active',
    ];

    protected $casts = [
        'how_to_earn' => 'array',
        'terms_conditions' => 'array',
        'is_active' => 'boolean',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public static function typeOptions(): array
    {
        return self::TYPES;
    }
}
