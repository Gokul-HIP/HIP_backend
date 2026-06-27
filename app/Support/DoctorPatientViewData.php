<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DoctorPatientViewData
{
    public static function buildPatientProfile(DoctorBooking $booking, ?Doctor $doctor): array
    {
        $patient = $booking->patient;
        $member = $booking->member;
        $hipUser = $patient?->hipUser;

        $name = trim(collect([
            $patient?->first_name,
            $patient?->last_name,
        ])->filter()->join(' '));

        if ($name === '') {
            $name = trim((string) ($booking->name ?: $member?->name ?: 'Patient'));
        }

        $dob = $patient?->dob ?: $member?->dob;
        $age = $dob ? Carbon::parse($dob)->age : null;
        $gender = ucfirst((string) ($patient?->gender ?: $member?->gender ?: ''));
        $uhid = $member?->hip_id ?: ($patient?->id ?? $booking->patient_id ?? '—');
        $bloodGroup = $member?->blood_group ?: $hipUser?->blood_group;

        $mobile = $booking->mobile_number
            ?: $patient?->mobile
            ?: $member?->mobile_num
            ?: '—';

        $email = $patient?->email ?: $member?->email ?: '—';
        $address = self::formatPatientAddress($member);
        $avatarUrl = self::resolveAvatarUrl($patient, $member);
        $initials = strtoupper(substr(preg_replace('/\s+/', ' ', trim($name)), 0, 2)) ?: 'P';

        $emergencyName = $member?->emergency_contact_person_name;
        $emergencyRelation = $member?->emergency_contact_person_relationship;
        $emergencyMobile = $member?->emergency_contact_person_phone;

        $emergencyContact = collect([
            $emergencyName,
            $emergencyRelation ? '('.$emergencyRelation.')' : null,
        ])->filter()->implode(' ');

        $patientBookings = self::patientBookings($booking, $doctor);

        $appointmentTimeline = $patientBookings
            ->filter(function (DoctorBooking $item) {
                return $item->booking_date
                    && $item->status !== 'cancelled'
                    && $item->appointment_status !== DoctorBooking::APPOINTMENT_STATUS_CANCELLED;
            })
            ->sortBy(fn (DoctorBooking $item) => $item->booking_date?->timestamp ?? PHP_INT_MAX)
            ->map(fn (DoctorBooking $item) => self::formatTimelineEntry($item))
            ->values()
            ->all();

        $latestPrescription = self::latestPrescription($booking, $doctor);
        $currentMedications = self::formatMedicationSummary($latestPrescription?->medications ?? []);
        $medicalHistory = filled($latestPrescription?->diagnosis)
            ? (string) $latestPrescription->diagnosis
            : null;

        $prescriptionDoctor = $latestPrescription?->doctor?->name
            ? 'Dr. '.$latestPrescription->doctor->name
            : ($doctor?->name ? 'Dr. '.$doctor->name : '—');

        return [
            'booking_id' => $booking->id,
            'patient_id' => $booking->patient_id,
            'member_id' => $booking->member_id,
            'name' => $name,
            'uhid' => $uhid,
            'age' => $age,
            'gender' => $gender,
            'blood_group' => $bloodGroup,
            'mobile' => $mobile,
            'email' => $email,
            'address' => $address,
            'emergency_contact' => $emergencyContact ?: '—',
            'emergency_mobile' => $emergencyMobile ?: '—',
            'allergies' => null,
            'medical_history' => $medicalHistory,
            'current_medications' => $currentMedications ?: '—',
            'avatar_url' => $avatarUrl,
            'initials' => $initials,
            'avatar_color' => self::avatarColor($name),
            'primary_doctor' => $doctor?->name ? 'Dr. '.$doctor->name : '—',
            'recent_lab' => [
                'title' => '—',
                'subtitle' => 'No recent lab reports',
                'when' => '—',
            ],
            'recent_prescription' => [
                'title' => 'Prescription',
                'subtitle' => $prescriptionDoctor,
                'when' => $latestPrescription?->created_at?->format('d M Y') ?? '—',
            ],
            'appointment_timeline' => $appointmentTimeline,
        ];
    }

    public static function buildHistoryPatient(DoctorBooking $booking, ?Doctor $doctor): array
    {
        $profile = self::buildPatientProfile($booking, $doctor);

        return [
            'avatar_url' => $profile['avatar_url'],
            'name' => $profile['name'],
            'mobile' => $profile['mobile'],
            'uhid' => $profile['uhid'],
            'primary_doctor' => $profile['primary_doctor'],
            'age' => $profile['age'],
            'gender' => $profile['gender'],
            'initials' => $profile['initials'],
            'avatar_color' => $profile['avatar_color'],
            'booking_id' => $booking->id,
        ];
    }

    public static function buildHistoryStats(Collection $bookings): array
    {
        $today = now()->startOfDay();

        return [
            'total' => $bookings->count(),
            'completed' => $bookings->filter(fn (DoctorBooking $b) => self::historyStatusKey($b, $today) === 'completed')->count(),
            'upcoming' => $bookings->filter(fn (DoctorBooking $b) => self::historyStatusKey($b, $today) === 'upcoming')->count(),
            'cancelled' => $bookings->filter(fn (DoctorBooking $b) => self::historyStatusKey($b, $today) === 'cancelled')->count(),
        ];
    }

    public static function mapHistoryRow(DoctorBooking $booking): array
    {
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $time = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $department = $booking->department?->name
            ?: (($booking->doctor?->speciality_names ?? '-') !== '-'
                ? $booking->doctor?->speciality_names
                : 'General Medicine');

        $branch = $booking->branch?->name ?: $booking->hospital?->name ?: '—';
        $doctorName = $booking->doctor?->name ? 'Dr. '.$booking->doctor->name : '—';
        $today = now()->startOfDay();
        $statusKey = self::historyStatusKey($booking, $today);

        return [
            'id' => $booking->id,
            'appointment_id' => 'APT-'.str_pad((string) $booking->id, 6, '0', STR_PAD_LEFT),
            'date' => $booking->booking_date?->format('d M Y') ?? '—',
            'time' => $time,
            'department' => $department,
            'doctor' => $doctorName,
            'branch' => $branch,
            'type' => self::visitTypeLabel($booking),
            'type_key' => self::visitTypeKey($booking),
            'status' => ucfirst($statusKey),
            'status_key' => $statusKey,
        ];
    }

    public static function historyStatusKey(DoctorBooking $booking, Carbon $today): string
    {
        if ($booking->status === 'cancelled'
            || $booking->appointment_status === DoctorBooking::APPOINTMENT_STATUS_CANCELLED) {
            return 'cancelled';
        }

        if ($booking->appointment_status === DoctorBooking::APPOINTMENT_STATUS_COMPLETED
            || $booking->status === 'completed') {
            return 'completed';
        }

        if ($booking->booking_date && $booking->booking_date->greaterThanOrEqualTo($today)) {
            return 'upcoming';
        }

        if ($booking->booking_date && $booking->booking_date->lt($today)) {
            return 'completed';
        }

        return 'upcoming';
    }

    public static function visitTypeKey(DoctorBooking $booking): string
    {
        if ($booking->isOnlineConsultation()) {
            return 'online';
        }

        if ($booking->is_follow_up) {
            return 'follow-up';
        }

        return 'in-clinic';
    }

    public static function visitTypeLabel(DoctorBooking $booking): string
    {
        return match (self::visitTypeKey($booking)) {
            'online' => 'Online',
            'follow-up' => 'Follow-up',
            default => 'In-clinic',
        };
    }

    public static function patientBookings(DoctorBooking $booking, ?Doctor $doctor): Collection
    {
        if (! $doctor) {
            return collect([$booking]);
        }

        return DoctorBooking::query()
            ->with(['patient.hipUser', 'member', 'hospital', 'branch', 'department', 'doctor'])
            ->where('doctor_id', $doctor->id)
            ->where(fn ($query) => PatientRecordScope::apply(
                $query,
                $booking->patient_id,
                $booking->member_id
            ))
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->get();
    }

    public static function filteredHistoryBookings(
        Collection $bookings,
        string $search,
        string $typeFilter,
        string $statusFilter,
        ?string $dateFrom,
        ?string $dateTo
    ): Collection {
        $search = strtolower(trim($search));
        $today = now()->startOfDay();

        return $bookings
            ->when($search !== '', function (Collection $items) use ($search) {
                return $items->filter(function (DoctorBooking $booking) use ($search) {
                    $row = self::mapHistoryRow($booking);

                    return str_contains(strtolower($row['appointment_id']), $search)
                        || str_contains(strtolower($row['doctor']), $search)
                        || str_contains(strtolower($row['department']), $search)
                        || str_contains(strtolower($row['branch']), $search);
                });
            })
            ->when($typeFilter !== 'all', function (Collection $items) use ($typeFilter) {
                return $items->filter(function (DoctorBooking $booking) use ($typeFilter) {
                    if ($typeFilter === 'new') {
                        return ! $booking->is_follow_up && ! $booking->isOnlineConsultation();
                    }

                    return self::visitTypeKey($booking) === $typeFilter;
                });
            })
            ->when($statusFilter !== 'all', function (Collection $items) use ($statusFilter, $today) {
                return $items->filter(
                    fn (DoctorBooking $booking) => self::historyStatusKey($booking, $today) === $statusFilter
                );
            })
            ->when(filled($dateFrom), function (Collection $items) use ($dateFrom) {
                $from = Carbon::parse($dateFrom)->startOfDay();

                return $items->filter(
                    fn (DoctorBooking $booking) => $booking->booking_date && $booking->booking_date->greaterThanOrEqualTo($from)
                );
            })
            ->when(filled($dateTo), function (Collection $items) use ($dateTo) {
                $to = Carbon::parse($dateTo)->endOfDay();

                return $items->filter(
                    fn (DoctorBooking $booking) => $booking->booking_date && $booking->booking_date->lessThanOrEqualTo($to)
                );
            })
            ->sortByDesc(fn (DoctorBooking $booking) => $booking->booking_date?->timestamp ?? 0)
            ->values();
    }

    protected static function latestPrescription(DoctorBooking $booking, ?Doctor $doctor): ?Prescription
    {
        if (! $doctor) {
            return null;
        }

        return Prescription::query()
            ->with('doctor')
            ->where('doctor_id', $doctor->id)
            ->where(fn ($query) => PatientRecordScope::apply(
                $query,
                $booking->patient_id,
                $booking->member_id
            ))
            ->orderByDesc('created_at')
            ->first();
    }

    protected static function formatPatientAddress(?HIPUser $member): ?string
    {
        $parts = array_filter([
            $member?->house_number,
            $member?->street,
            $member?->city,
            $member?->state,
            $member?->zip_code,
        ]);

        return $parts !== [] ? implode(', ', $parts) : null;
    }

    protected static function formatMedicationSummary(array $medications): ?string
    {
        $items = collect($medications)
            ->map(function (array $med) {
                $name = trim((string) ($med['name'] ?? ''));
                if ($name === '') {
                    return null;
                }

                $dosage = trim((string) ($med['dosage'] ?? ''));
                $frequency = self::medicationFrequencyShort($med['frequency'] ?? null);
                $label = $name;

                if ($dosage !== '') {
                    $label .= ' '.$dosage;
                }

                if ($frequency !== '') {
                    $label .= ' ('.$frequency.')';
                }

                return $label;
            })
            ->filter()
            ->values();

        return $items->isNotEmpty() ? $items->implode(', ') : null;
    }

    protected static function medicationFrequencyShort(?string $frequency): string
    {
        $value = strtolower(trim((string) $frequency));

        return match (true) {
            str_contains($value, 'once') || $value === 'daily' => 'QD',
            str_contains($value, 'twice') => 'BD',
            str_contains($value, 'thrice') || str_contains($value, 'three') => 'TDS',
            default => $frequency ?? '',
        };
    }

    protected static function formatTimelineEntry(DoctorBooking $booking): array
    {
        $today = now()->startOfDay();
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $time = match ($slots->count()) {
            0 => '—',
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $branch = $booking->branch?->name ?: $booking->hospital?->name ?: 'OPD';
        $visitLabel = $booking->is_follow_up
            ? 'Follow-up Visit'
            : ($booking->isOnlineConsultation() ? 'Online Consultation' : 'Consultation');

        $line = ($booking->booking_date?->format('d M Y') ?? '—').' • '.$time.' • '.$branch;

        $isUpcoming = $booking->booking_date
            && $booking->booking_date->greaterThanOrEqualTo($today)
            && $booking->status !== 'cancelled'
            && $booking->appointment_status !== DoctorBooking::APPOINTMENT_STATUS_CANCELLED;

        if ($isUpcoming) {
            return [
                'label' => 'Upcoming Appointment',
                'line' => $line,
                'class' => 'upcoming',
            ];
        }

        return [
            'label' => 'Consultation',
            'line' => ($booking->booking_date?->format('d M Y') ?? '—').' • '.$visitLabel,
            'class' => 'past',
        ];
    }

    public static function resolveAvatarUrl(?Persons $patient, ?HIPUser $member): ?string
    {
        $candidates = array_filter([
            filled($patient?->image) ? 'users/'.ltrim((string) $patient->image, '/') : null,
            filled($member?->profile_image) ? 'users/'.ltrim((string) $member->profile_image, '/') : null,
        ]);

        foreach ($candidates as $path) {
            if (Storage::disk('public')->exists($path)) {
                return asset('storage/'.$path);
            }
        }

        return null;
    }

    public static function avatarColor(string $name): string
    {
        $colors = ['#c8102e', '#0da2e7', '#059669', '#7c3aed', '#e09b1a', '#0369a1'];

        return $colors[crc32($name) % count($colors)];
    }
}
