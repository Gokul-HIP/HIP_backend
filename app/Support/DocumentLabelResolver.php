<?php

namespace App\Support;

use App\Models\DiagnosticTestBooking;
use App\Models\Document;
use App\Models\Persons;

class DocumentLabelResolver
{
    public static function isDiagnosticCenterUpload(Document $document): bool
    {
        return filled($document->diagnostic_test_booking_id);
    }

    public static function diagnosticCenterLabel(?Document $document = null): string
    {
        $booking = $document?->relationLoaded('diagnosticTestBooking')
            ? $document->diagnosticTestBooking
            : ($document?->diagnostic_test_booking_id
                ? $document->diagnosticTestBooking()->with('diagnosticCenter')->first()
                : null);

        $centerName = trim((string) ($booking?->diagnosticCenter?->name ?? ''));

        return $centerName !== '' ? $centerName : 'Diagnostic Center';
    }

    public static function resolveUploadedBy(Document $document): string
    {
        if (self::isDiagnosticCenterUpload($document)) {
            return self::diagnosticCenterLabel($document);
        }

        if ($document->relationLoaded('member') ? $document->member : $document->member()->first()) {
            $member = $document->member;
            $name = trim(collect([
                $member?->first_name,
                $member?->last_name,
            ])->filter()->join(' '));

            if ($name !== '') {
                return $name;
            }
        }

        if ($document->relationLoaded('patient') ? $document->patient : $document->patient()->first()) {
            $patient = $document->patient;
            $name = trim(collect([
                $patient?->first_name,
                $patient?->last_name,
            ])->filter()->join(' '));

            if ($name !== '') {
                return $name;
            }
        }

        return 'Patient Upload';
    }

    public static function resolvePatientIdForBooking(DiagnosticTestBooking $booking): ?string
    {
        if (filled($booking->patient_id)) {
            return (string) $booking->patient_id;
        }

        if (! $booking->member_id) {
            return null;
        }

        $primaryPersonId = Persons::query()
            ->where('hip_user_id', $booking->member_id)
            ->where('is_primary', true)
            ->value('id');

        if ($primaryPersonId) {
            return (string) $primaryPersonId;
        }

        return Persons::query()
            ->where('hip_user_id', $booking->member_id)
            ->value('id');
    }

    public static function formatDocumentTypeLabel(?string $documentType): string
    {
        $label = ucwords(str_replace(['_', '-'], ' ', trim((string) ($documentType ?: 'report'))));

        return $label !== '' ? $label : 'Report';
    }
}
