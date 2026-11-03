<?php

namespace App\Enums;

enum HmsPaymentType: string
{
    case OpConsultation = 'op_consultation';
    case LabRadiology = 'lab_radiology';
    case OpPharmacy = 'op_pharmacy';
    case Appointment = 'appointment';
    case LabTestBooking = 'lab_test_booking';
    case DiagnosticBooking = 'diagnostic_booking';
    case IpFinalSettlement = 'ip_final_settlement';
    case IpAdvanceDeposit = 'ip_advance_deposit';
    case IpProvisionalBill = 'ip_provisional_bill';
    case IpLabPharmacyInFinalBill = 'ip_lab_pharmacy_in_final_bill';
    case Membership = 'membership';

    public function isEligible(): bool
    {
        return match ($this) {
            self::OpConsultation,
            self::LabRadiology,
            self::OpPharmacy,
            self::Appointment,
            self::LabTestBooking,
            self::DiagnosticBooking,
            self::IpFinalSettlement => true,
            default => false,
        };
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
            self::IpAdvanceDeposit => 'IP advance deposit',
            self::IpProvisionalBill => 'IP provisional bill',
            self::IpLabPharmacyInFinalBill => 'IP lab & pharmacy in final bill',
            self::Membership => 'Membership',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = ['all' => 'All Services'];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
