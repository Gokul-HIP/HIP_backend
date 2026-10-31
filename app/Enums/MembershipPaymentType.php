<?php

namespace App\Enums;

enum MembershipPaymentType: string
{
    case OpConsultation = 'op_consultation';
    case LabRadiology = 'lab_radiology';
    case OpPharmacy = 'op_pharmacy';
    case Appointment = 'appointment';
    case LabTestBooking = 'lab_test_booking';
    case DiagnosticBooking = 'diagnostic_booking';
    case IpFinalSettlement = 'ip_final_settlement';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type) => $type->value, self::cases());
    }

    /**
     * @return array<string, float>
     */
    public static function blankPercentages(): array
    {
        return array_fill_keys(self::values(), 0.0);
    }

    public function label(): string
    {
        return match ($this) {
            self::OpConsultation => 'OP consultation',
            self::LabRadiology => 'Lab and radiology',
            self::OpPharmacy => 'OP pharmacy',
            self::Appointment => 'Appointment',
            self::LabTestBooking => 'Lab test booking',
            self::DiagnosticBooking => 'Diagnostic booking',
            self::IpFinalSettlement => 'IP final settlement',
        };
    }
}
