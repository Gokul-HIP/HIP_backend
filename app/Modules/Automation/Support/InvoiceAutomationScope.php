<?php

namespace App\Modules\Automation\Support;

use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Models\Invoice;
use App\Models\SecondOpinion;
use Illuminate\Support\Facades\Schema;

/**
 * Hospital/organization scope for invoices used by Payment Pending automation.
 *
 * Matches Invoice::scopeForHospital and PaymentApiService invoice_details:
 * booking hospital, second-opinion/diagnostic branch, creator hospital, then
 * legacy invoice_details JSON (doctor_booking_id / branch_id) when FK columns are null.
 * branch_id on bookings and invoice_details is hospitals.id.
 */
final class InvoiceAutomationScope
{
    public static function hydrate(Invoice $invoice): Invoice
    {
        $invoice->loadMissing([
            'person',
            'primaryPerson',
            'creator',
            'doctorBooking.hospital.organization',
            'doctorBooking.doctor',
            'doctorBooking.patient',
            'secondOpinion',
            'diagnosticTestBooking',
        ]);

        return $invoice;
    }

    public static function hospitalId(Invoice $invoice): ?int
    {
        self::hydrate($invoice);

        $fromBooking = self::hospitalIdFromDoctorBooking($invoice->doctorBooking);
        if ($fromBooking !== null) {
            return $fromBooking;
        }

        $secondOpinionBranch = self::intId($invoice->secondOpinion?->branch_id);
        if ($secondOpinionBranch !== null) {
            return $secondOpinionBranch;
        }

        $diagnosticBranch = self::intId($invoice->diagnosticTestBooking?->branch_id);
        if ($diagnosticBranch !== null) {
            return $diagnosticBranch;
        }

        $creatorHospitalId = self::intId($invoice->creator?->hospital_id);
        if ($creatorHospitalId !== null) {
            return $creatorHospitalId;
        }

        return self::hospitalIdFromInvoiceDetails($invoice);
    }

    public static function hospital(Invoice $invoice): ?Hospital
    {
        self::hydrate($invoice);

        $fromBooking = $invoice->doctorBooking?->hospital;
        if ($fromBooking instanceof Hospital) {
            return $fromBooking;
        }

        $hospitalId = self::hospitalId($invoice);
        if ($hospitalId === null) {
            return null;
        }

        try {
            if (! Schema::hasTable('hospitals')) {
                return null;
            }
        } catch (\Throwable) {
            return null;
        }

        $hospital = Hospital::query()->find($hospitalId);

        return $hospital instanceof Hospital ? $hospital : null;
    }

    public static function organizationId(Invoice $invoice): ?int
    {
        $hospital = self::hospital($invoice);
        if ($hospital?->organization_id) {
            return (int) $hospital->organization_id;
        }

        self::hydrate($invoice);
        $creatorOrg = $invoice->creator?->organization_id;

        return $creatorOrg ? (int) $creatorOrg : null;
    }

    protected static function hospitalIdFromDoctorBooking(mixed $booking): ?int
    {
        if (! $booking instanceof DoctorBooking) {
            return null;
        }

        return self::intId($booking->hospital_id) ?? self::intId($booking->branch_id);
    }

    /**
     * Legacy invoices often leave FK columns null and store the booking/branch
     * on invoice_details in the same shape PaymentApiService writes today.
     */
    protected static function hospitalIdFromInvoiceDetails(Invoice $invoice): ?int
    {
        $rows = self::invoiceDetailRows($invoice);

        foreach ($rows as $row) {
            $bookingId = self::intId($row['doctor_booking_id'] ?? null);
            if ($bookingId !== null && self::hasTable('doctor_bookings')) {
                $booking = DoctorBooking::query()->find($bookingId);
                $fromBooking = self::hospitalIdFromDoctorBooking($booking);
                if ($fromBooking !== null) {
                    return $fromBooking;
                }
            }

            $secondOpinionId = self::intId($row['second_opinion_id'] ?? null);
            if ($secondOpinionId !== null && self::hasTable('second_opinions')) {
                $branch = self::intId(SecondOpinion::query()->find($secondOpinionId)?->branch_id);
                if ($branch !== null) {
                    return $branch;
                }
            }

            $diagnosticId = self::intId($row['diagnostic_test_booking_id'] ?? null);
            if ($diagnosticId !== null && self::hasTable('diagnostic_test_bookings')) {
                $branch = self::intId(DiagnosticTestBooking::query()->find($diagnosticId)?->branch_id);
                if ($branch !== null) {
                    return $branch;
                }
            }

            $fromJson = self::intId($row['branch_id'] ?? $row['hospital_id'] ?? null);
            if ($fromJson !== null) {
                return $fromJson;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected static function invoiceDetailRows(Invoice $invoice): array
    {
        $details = $invoice->invoice_details;

        if (! is_array($details) || $details === []) {
            return [];
        }

        if (array_is_list($details)) {
            return array_values(array_filter($details, 'is_array'));
        }

        return self::looksLikeDetailRow($details) ? [$details] : [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected static function looksLikeDetailRow(array $row): bool
    {
        return isset($row['doctor_booking_id'])
            || isset($row['second_opinion_id'])
            || isset($row['diagnostic_test_booking_id'])
            || isset($row['branch_id'])
            || isset($row['hospital_id']);
    }

    protected static function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function intId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
