<?php

namespace App\Support;

use App\Models\DiagnosticTestBooking;
use App\Models\HIPUser;
use App\Models\Persons;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TechnicianPatientViewData
{
    public static function patientName(DiagnosticTestBooking $booking): string
    {
        $patient = $booking->patient;
        $name = trim(collect([$patient?->first_name, $patient?->last_name])->filter()->join(' '));

        if ($name === '') {
            $name = trim((string) ($booking->name ?: $booking->member?->name ?: 'Patient'));
        }

        return $name;
    }

    public static function initials(string $name): string
    {
        $clean = preg_replace('/\s+/', ' ', trim($name)) ?: 'P';

        return strtoupper(substr($clean, 0, 2)) ?: 'P';
    }

    public static function buildPatientSummary(DiagnosticTestBooking $booking): array
    {
        $patient = $booking->patient;
        $member = $booking->member;
        $name = self::patientName($booking);
        $dob = $patient?->dob ?: $member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $gender = ucfirst((string) ($patient?->gender ?: $member?->gender ?: '—'));
        $uhid = $member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');
        $mobile = $booking->mobile_number ?: $patient?->mobile ?: $member?->mobile_num;

        return [
            'name' => $name,
            'initials' => self::initials($name),
            'uhid' => $uhid,
            'age' => $age,
            'gender' => $gender,
            'mobile' => $mobile ? '+91 ' . $mobile : '—',
            'last_visit' => $booking->booking_date?->format('d M Y') ?: '—',
            'status' => 'Active Patient',
            'booking_id' => $booking->id,
        ];
    }

    public static function buildPatientProfile(DiagnosticTestBooking $booking): array
    {
        $patient = $booking->patient;
        $member = $booking->member;
        $name = self::patientName($booking);
        $dob = $patient?->dob ?: $member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $gender = ucfirst((string) ($patient?->gender ?: $member?->gender ?: '—'));

        $addressParts = array_filter([
            $member?->house_number,
            $member?->street,
            $member?->city,
            $member?->state,
            $member?->pincode,
        ]);

        $bookings = self::patientBookings($booking);

        return [
            'booking_id' => $booking->id,
            'name' => $name,
            'initials' => self::initials($name),
            'uhid' => $member?->hip_id ?: ($patient?->id ?? '—'),
            'age' => $age,
            'gender' => $gender,
            'blood_group' => $member?->blood_group ?: $patient?->blood_group,
            'mobile' => $booking->mobile_number ?: $patient?->mobile ?: $member?->mobile_num ?: '—',
            'email' => $patient?->email ?: $member?->email ?: '—',
            'address' => $addressParts ? implode(', ', $addressParts) : null,
            'emergency_contact' => $member?->emergency_contact_name ?: '—',
            'emergency_mobile' => $member?->emergency_contact_number ?: '—',
            'allergies' => $patient?->allergies ?? $member?->allergies,
            'medical_history' => $patient?->medical_history ?? $member?->medical_history,
            'current_medications' => '—',
            'appointment_timeline' => $bookings->take(5)->map(function (DiagnosticTestBooking $item) {
                $status = strtolower((string) ($item->status ?? 'pending'));

                return [
                    'label' => $item->booking_date?->format('d M Y') ?: '—',
                    'line' => 'Diagnostic booking • ' . ucfirst($status) . ' • APT-' . str_pad((string) $item->id, 4, '0', STR_PAD_LEFT),
                    'class' => match ($status) {
                        'completed' => 'completed',
                        'cancelled' => 'cancelled',
                        'confirmed' => 'confirmed',
                        default => 'pending',
                    },
                ];
            })->values()->all(),
        ];
    }

    public static function patientBookings(DiagnosticTestBooking $booking): Collection
    {
        return DiagnosticTestBooking::query()
            ->with(['diagnosticCenter', 'branch'])
            ->when($booking->patient_id, fn ($q) => $q->where('patient_id', $booking->patient_id))
            ->when(! $booking->patient_id && $booking->member_id, fn ($q) => $q->where('member_id', $booking->member_id))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get();
    }

    public static function buildHistoryStats(Collection $bookings): array
    {
        return [
            'total' => $bookings->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'upcoming' => $bookings->filter(fn ($b) => $b->booking_date && $b->booking_date->gte(now()->startOfDay()) && $b->status !== 'cancelled')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
        ];
    }
}
