<?php

namespace App\Modules\Automation\Engine;

use App\Models\DoctorBooking;
use App\Models\Persons;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * Generic runtime facts for expressions, waits, recipients, and templates.
 *
 * Derivation is always: payload already on the event, else a query scoped to
 * the SAME patient_id + SAME hospital_id. Never picks an unrelated latest booking
 * when the event already carries that entity.
 *
 * Facts:
 * - appointment.exists: any non-cancelled booking for this patient+hospital (live query when ids present)
 * - appointment.department: event booking department name/code, not another booking
 * - appointment.status: DoctorBooking lifecycle status (pending|confirmed|cancelled|completed|missed),
 *   reloaded by appointment_id when that booking exists
 * - appointment.appointment_status: visit/attendance state (new_scheduled|checked_in|completed|cancelled)
 * - followup.exists / followup.date: payload, then event prescription, then scoped follow-up booking/prescription
 * - last_visit: whole days since last completed booking for this patient+hospital
 * - patient.age: payload age or computed from dob
 * - patient.relationship / gender: from patient or event booking
 * - caregiver_contact: payload, then parent person mobile, then member emergency phone
 * - booking_link: payload or event booking online_consultation_link
 * - hospital_phone: payload or hospital admin_contact
 */
class AutomationFactsBuilder
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function enrich(array $payload): array
    {
        $payload = $this->hydrateAppointmentById($payload);

        $patient = $payload['patient'] ?? null;
        $hospital = $payload['hospital'] ?? null;
        $appointment = $payload['appointment'] ?? null;
        $prescription = $payload['prescription'] ?? null;

        $patientId = $this->scalarId($payload['patient_id'] ?? $this->attr($patient, 'id'));
        $hospitalId = $this->intId($payload['hospital_id'] ?? $this->attr($hospital, 'id') ?? $this->attr($appointment, 'hospital_id'));

        $appointmentExists = $this->resolveAppointmentExists($payload, $patientId, $hospitalId);
        $department = $this->resolveAppointmentDepartment($payload, $appointment);
        $lifecycleStatus = $this->attr($appointment, 'status');
        $visitStatus = $this->attr($appointment, 'appointment_status')
            ?? $payload['appointment_status']
            ?? null;

        $followup = $this->resolveFollowup($payload, $prescription, $patientId, $hospitalId);

        $facts = [
            'appointment' => [
                'exists' => $appointmentExists,
                'department' => $department,
                'status' => $lifecycleStatus,
                'appointment_status' => $visitStatus,
            ],
            'followup' => $followup,
            'patient' => [
                'age' => $this->resolvePatientAge($payload, $patient),
                'gender' => $this->attr($patient, 'gender'),
                'relationship' => $this->attr($patient, 'relationship')
                    ?? $this->attr($appointment, 'relationship')
                    ?? $payload['relationship']
                    ?? null,
            ],
            'last_visit' => $this->resolveLastVisitDays($payload, $patientId, $hospitalId),
        ];

        $payload['_facts'] = $facts;
        $payload['last_visit'] = $facts['last_visit'];
        $payload['followup'] = array_merge(
            is_array($payload['followup'] ?? null) ? $payload['followup'] : [],
            $followup
        );
        $payload['followup_date'] = $followup['date'] ?? ($payload['followup_date'] ?? null);
        $payload['caregiver_contact'] = $this->resolveCaregiverContact($payload, $patient);
            $payload['booking_link'] = $payload['booking_link']
            ?? $this->attr($appointment, 'online_consultation_link')
            ?? null;
        $payload['hospital_phone'] = $payload['hospital_phone']
            ?? $this->attr($hospital, 'admin_contact')
            ?? $this->attr($hospital, 'admin_emergency_contact')
            ?? $this->attr($hospital, 'ambulance_number')
            ?? null;

        if (is_array($patient)) {
            $payload['patient'] = array_merge($patient, array_filter(
                $facts['patient'],
                fn ($value) => $value !== null
            ));
        }

        return $payload;
    }

    /**
     * Reload the SAME DoctorBooking by persisted appointment_id so post-wait
     * conditions see current lifecycle status, not the JSON snapshot from start.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function hydrateAppointmentById(array $payload): array
    {
        $appointmentId = $this->scalarId(
            $payload['appointment_id'] ?? $this->attr($payload['appointment'] ?? null, 'id')
        );

        if ($appointmentId === null || ! $this->hasTable('doctor_bookings')) {
            return $payload;
        }

        $live = DoctorBooking::query()->find($appointmentId);

        if (! $live) {
            return $payload;
        }

        $payload['appointment'] = $live;
        $payload['appointment_id'] = $live->id;
        $payload['appointment_status'] = $live->appointment_status;

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveAppointmentExists(array $payload, mixed $patientId, ?int $hospitalId): bool
    {
        if ($patientId !== null && $hospitalId !== null && $this->hasTable('doctor_bookings')) {
            return DoctorBooking::query()
                ->where('patient_id', $patientId)
                ->where('hospital_id', $hospitalId)
                ->where(function ($query) {
                    $query->whereNull('status')
                        ->orWhere('status', '!=', DoctorBooking::STATUS_CANCELLED);
                })
                ->exists();
        }

        if (is_array($payload['appointment'] ?? null) && is_bool($payload['appointment']['exists'] ?? null)) {
            return (bool) $payload['appointment']['exists'];
        }

        if (($payload['appointment'] ?? null) instanceof DoctorBooking) {
            return true;
        }

        if (isset($payload['appointment_id']) && $payload['appointment_id'] !== null && $payload['appointment_id'] !== '') {
            return true;
        }

        if (is_array($payload['appointment'] ?? null) && $payload['appointment'] !== []) {
            return true;
        }

        return false;
    }

    protected function resolveAppointmentDepartment(array $payload, mixed $appointment): mixed
    {
        if (isset($payload['appointment']) && is_array($payload['appointment']) && array_key_exists('department', $payload['appointment'])) {
            $department = $payload['appointment']['department'];

            if (is_array($department)) {
                return $department['name'] ?? $department['department_name'] ?? null;
            }

            return $department;
        }

        $related = $this->attr($appointment, 'department');

        if (is_object($related) || is_array($related)) {
            return $this->attr($related, 'name') ?? $this->attr($related, 'department_name');
        }

        if (is_string($related) && $related !== '') {
            return $related;
        }

        return $payload['appointment_department'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{exists: bool, date: mixed}
     */
    protected function resolveFollowup(array $payload, mixed $prescription, mixed $patientId, ?int $hospitalId): array
    {
        if (is_array($payload['followup'] ?? null)) {
            $existing = $payload['followup'];
            if (array_key_exists('exists', $existing) || array_key_exists('date', $existing)) {
                $date = $existing['date'] ?? $payload['followup_date'] ?? null;

                return [
                    'exists' => (bool) ($existing['exists'] ?? $this->isPresentDate($date)),
                    'date' => $this->normalizeDate($date),
                ];
            }
        }

        if (array_key_exists('followup_date', $payload) && $this->isPresentDate($payload['followup_date'])) {
            return ['exists' => true, 'date' => $this->normalizeDate($payload['followup_date'])];
        }

        $fromPrescription = $this->attr($prescription, 'follow_up_date');
        if ($this->isPresentDate($fromPrescription)) {
            return ['exists' => true, 'date' => $this->normalizeDate($fromPrescription)];
        }

        $followUpBooking = $this->attr($prescription, 'followUpBooking')
            ?? $this->attr($prescription, 'follow_up_booking');
        $followUpBookingDate = $this->attr($followUpBooking, 'booking_date');
        if ($this->isPresentDate($followUpBookingDate)) {
            return ['exists' => true, 'date' => $this->normalizeDate($followUpBookingDate)];
        }

        if ($patientId !== null && $hospitalId !== null && $this->hasTable('prescriptions')) {
            $row = Prescription::query()
                ->where('patient_id', $patientId)
                ->where('hospital_id', $hospitalId)
                ->whereNotNull('follow_up_date')
                ->orderByDesc('follow_up_date')
                ->first();

            if ($row && $this->isPresentDate($row->follow_up_date)) {
                return ['exists' => true, 'date' => $this->normalizeDate($row->follow_up_date)];
            }
        }

        if ($patientId !== null && $hospitalId !== null && $this->hasTable('doctor_bookings')) {
            $booking = DoctorBooking::query()
                ->where('patient_id', $patientId)
                ->where('hospital_id', $hospitalId)
                ->where('is_follow_up', true)
                ->whereDate('booking_date', '>=', now()->toDateString())
                ->orderBy('booking_date')
                ->first();

            if ($booking && $this->isPresentDate($booking->booking_date)) {
                return ['exists' => true, 'date' => $this->normalizeDate($booking->booking_date)];
            }
        }

        return ['exists' => false, 'date' => null];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveLastVisitDays(array $payload, mixed $patientId, ?int $hospitalId): mixed
    {
        if (isset($payload['last_visit']) && is_numeric($payload['last_visit'])) {
            return (int) $payload['last_visit'];
        }

        if ($patientId === null || $hospitalId === null || ! $this->hasTable('doctor_bookings')) {
            return null;
        }

        $booking = DoctorBooking::query()
            ->where('patient_id', $patientId)
            ->where('hospital_id', $hospitalId)
            ->where(function ($query) {
                $query->where('appointment_status', DoctorBooking::APPOINTMENT_STATUS_COMPLETED)
                    ->orWhere('status', DoctorBooking::STATUS_COMPLETED);
            })
            ->orderByDesc('booking_date')
            ->first();

        if (! $booking || ! $this->isPresentDate($booking->booking_date)) {
            return null;
        }

        return (int) Carbon::parse($booking->booking_date)->startOfDay()->diffInDays(now()->startOfDay());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolvePatientAge(array $payload, mixed $patient): mixed
    {
        $age = $this->attr($patient, 'age') ?? $payload['age'] ?? null;

        if (is_numeric($age)) {
            return (int) $age;
        }

        $dob = $this->attr($patient, 'dob') ?? $payload['dob'] ?? null;

        if (! $this->isPresentDate($dob)) {
            return null;
        }

        try {
            return Carbon::parse($dob)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveCaregiverContact(array $payload, mixed $patient): mixed
    {
        if (filled($payload['caregiver_contact'] ?? null)) {
            return $payload['caregiver_contact'];
        }

        $caregiver = $payload['caregiver'] ?? null;
        $fromCaregiver = $this->attr($caregiver, 'mobile')
            ?? $this->attr($caregiver, 'mobile_number')
            ?? $this->attr($caregiver, 'phone');
        if (filled($fromCaregiver)) {
            return $fromCaregiver;
        }

        $fromPatient = $this->attr($patient, 'caregiver_contact')
            ?? $this->attr($patient, 'guardian_mobile')
            ?? $this->attr($patient, 'parent_mobile');
        if (filled($fromPatient)) {
            return $fromPatient;
        }

        $parentId = $this->attr($patient, 'parent_id');
        if ($parentId && $this->hasTable('persons')) {
            $parent = Persons::query()->find($parentId);
            if ($parent && filled($parent->mobile)) {
                return $parent->mobile;
            }
        }

        $member = $payload['member'] ?? null;
        $emergency = $this->attr($member, 'emergency_contact_person_phone');
        if (filled($emergency)) {
            return $emergency;
        }

        return $payload['guardian_mobile'] ?? $payload['parent_mobile'] ?? null;
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function attr(mixed $entity, string $key, mixed $default = null): mixed
    {
        if ($entity === null) {
            return $default;
        }

        if (is_array($entity)) {
            return $entity[$key] ?? $default;
        }

        if (is_object($entity)) {
            return data_get($entity, $key, $default);
        }

        return $default;
    }

    protected function scalarId(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? $value : null;
    }

    protected function intId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function isPresentDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        return is_string($value) && trim($value) !== '';
    }

    protected function normalizeDate(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTimeImmutable::createFromInterface($value))->toDateString();
        }

        return $value;
    }
}
