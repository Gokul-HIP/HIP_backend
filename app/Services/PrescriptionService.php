<?php

namespace App\Services;

use App\Models\DoctorBooking;
use App\Models\Document;
use App\Models\Prescription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrescriptionService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected DoctorBookingStatusService $bookingStatusService
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $medications
     * @param  array<int, array<string, mixed>>  $labTests
     * @param  array<int, array<string, mixed>>  $uploadedDocuments
     */
    public function store(
        DoctorBooking $booking,
        array $medications,
        array $labTests,
        array $uploadedDocuments,
        ?string $clinicalNotes,
        ?string $followUpDate,
        string $status,
        bool $completeCurrentBooking = false
    ): Prescription {
        $context = DB::transaction(function () use (
            $booking,
            $medications,
            $labTests,
            $uploadedDocuments,
            $clinicalNotes,
            $followUpDate,
            $status,
            $completeCurrentBooking
        ) {
            $documentIds = $this->persistDocuments($booking, $uploadedDocuments);
            $followUpBooking = $this->createFollowUpBookingIfNeeded($booking, $followUpDate);

            $prescription = Prescription::create([
                'doctor_id' => $booking->doctor_id,
                'patient_id' => $booking->patient_id,
                'member_id' => $booking->member_id,
                'hospital_id' => $booking->hospital_id ?: $booking->branch_id,
                'doctor_booking_id' => $booking->id,
                'follow_up_booking_id' => $followUpBooking?->id,
                'medications' => $medications !== [] ? $this->normalizeMedications($medications) : null,
                'lab_tests' => $labTests !== [] ? $labTests : null,
                'document_ids' => $documentIds !== [] ? $documentIds : null,
                'clinical_notes' => filled($clinicalNotes) ? $clinicalNotes : null,
                'follow_up_date' => filled($followUpDate) ? $followUpDate : null,
                'vitals' => null,
                'diagnosis' => null,
                'status' => $status,
            ]);

            if ($completeCurrentBooking) {
                $this->bookingStatusService->updateAppointmentStatus(
                    $booking,
                    DoctorBooking::APPOINTMENT_STATUS_COMPLETED,
                    null,
                    false
                );
            }

            return [
                'prescription' => $prescription->fresh(),
                'followUpBooking' => $followUpBooking,
            ];
        });

        $prescription = $context['prescription'];
        $followUpBooking = $context['followUpBooking'];

        if ($followUpBooking) {
            $this->runNotificationSafely(fn () => $this->sendFollowUpNotification($followUpBooking));
        }

        if ($status === Prescription::STATUS_SENT) {
            $this->runNotificationSafely(fn () => $this->sendPrescriptionSentNotification($prescription, $booking));
        }

        if ($completeCurrentBooking) {
            $this->runNotificationSafely(fn () => $this->bookingStatusService->sendReviewNotification($booking->fresh()));
        }

        return $prescription;
    }

    protected function runNotificationSafely(callable $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $uploadedDocuments
     * @return array<int, int>
     */
    protected function persistDocuments(DoctorBooking $booking, array $uploadedDocuments): array
    {
        $documentIds = [];

        foreach ($uploadedDocuments as $doc) {
            $storedPath = (string) ($doc['stored_path'] ?? '');

            if ($storedPath === '' || ! Storage::disk('public')->exists($storedPath)) {
                continue;
            }

            if (! empty($doc['document_id'])) {
                $documentIds[] = (int) $doc['document_id'];

                continue;
            }

            $document = Document::create([
                'member_id' => $booking->member_id,
                'patient_id' => $booking->patient_id,
                'document_name' => (string) ($doc['name'] ?? $doc['file_name'] ?? 'Document'),
                'notes' => filled($doc['notes'] ?? null) ? (string) $doc['notes'] : null,
                'document_path' => $storedPath,
                'document_type' => (string) ($doc['type'] ?? 'other'),
                'document_size' => (string) ($doc['file_size_bytes'] ?? $doc['file_size'] ?? ''),
            ]);

            $documentIds[] = (int) $document->id;
        }

        return array_values(array_unique($documentIds));
    }

    /**
     * @param  array<int, array<string, mixed>>  $medications
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeMedications(array $medications): array
    {
        return collect($medications)
            ->filter(fn ($medication) => is_array($medication))
            ->map(function (array $medication) {
            return [
                'medicine_id' => $medication['medicine_id'] ?? null,
                'name' => (string) ($medication['name'] ?? 'Medicine'),
                'dosage' => filled($medication['dosage'] ?? null) ? (string) $medication['dosage'] : null,
                'frequency' => filled($medication['frequency'] ?? null) ? (string) $medication['frequency'] : null,
                'duration' => filled($medication['duration'] ?? null) ? (string) $medication['duration'] : null,
                'when_to_take' => filled($medication['when_to_take'] ?? null) ? (string) $medication['when_to_take'] : null,
                'quantity' => filled($medication['quantity'] ?? null) ? (string) $medication['quantity'] : null,
                'special_instruction' => filled($medication['special_instruction'] ?? $medication['special_instructions'] ?? null)
                    ? (string) ($medication['special_instruction'] ?? $medication['special_instructions'])
                    : null,
            ];
        })->values()->all();
    }

    protected function createFollowUpBookingIfNeeded(DoctorBooking $booking, ?string $followUpDate): ?DoctorBooking
    {
        if (! filled($followUpDate)) {
            return null;
        }

        $date = Carbon::parse($followUpDate)->startOfDay();

        if ($date->lt(now()->startOfDay())) {
            throw new \InvalidArgumentException('Follow-up date must be today or a future date.');
        }

        $followUp = DoctorBooking::create([
            'name' => $booking->name,
            'mobile_number' => $booking->mobile_number,
            'member_id' => $booking->member_id,
            'patient_id' => $booking->patient_id,
            'relationship' => $booking->relationship,
            'branch_id' => $booking->branch_id,
            'hospital_id' => $booking->hospital_id ?: $booking->branch_id,
            'doctor_id' => $booking->doctor_id,
            'department_id' => $booking->department_id,
            'appointment_type' => $booking->appointment_type,
            'consultation_type' => $booking->consultation_type ?: 'In-Person',
            'booking_date' => $date->toDateString(),
            'required_time_slots' => $booking->required_time_slots,
            'is_follow_up' => true,
            'reason_of_visit' => 'Follow-up appointment',
            'purpose' => 'Follow-up appointment scheduled from prescription',
            'message' => 'Follow-up appointment scheduled from prescription',
            'status' => 'confirmed',
            'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
            'payment_status' => 'unpaid',
        ]);

        return $followUp;
    }

    public function sendFollowUpNotification(DoctorBooking $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');

        $doctorName = trim((string) ($booking->doctor?->name ?? 'your doctor'));
        $appointmentDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'the scheduled date';

        $title = 'Follow-up appointment booked';
        $body = 'Your next follow-up with Dr. '.$doctorName.' has been booked for '.$appointmentDate.'.';

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, [
            'type' => 'follow_up_booking',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'doctor_name' => $doctorName,
            'follow_up_date' => $appointmentDate,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    protected function sendPrescriptionSentNotification(Prescription $prescription, DoctorBooking $booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        $booking->loadMissing('doctor');
        $doctorName = trim((string) ($booking->doctor?->name ?? 'your doctor'));

        $title = 'Prescription from your doctor';
        $body = 'Dr. '.$doctorName.' has sent your prescription. Open the app to view details.';

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, [
            'type' => 'prescription_sent',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'prescription_id' => (string) $prescription->id,
            'booking_id' => (string) $booking->id,
            'url' => '/prescriptions/'.$prescription->id,
            'route' => '/prescriptions/'.$prescription->id,
        ]);
    }
}
