<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorFollowUpService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public static function reasonOptions(): array
    {
        return [
            'review-reports' => 'Review Diagnostic / Lab Reports',
            'post-surgery' => 'Post-Surgery Follow-up',
            'medication-review' => 'Medication Review & Adjustment',
            'symptom-recheck' => 'Symptom Re-evaluation',
            'lab-result-discussion' => 'Discuss Lab / Imaging Results',
            'chronic-monitoring' => 'Chronic Condition Monitoring',
            'wound-care' => 'Wound Care & Dressing Check',
            'treatment-progress' => 'Treatment Progress Review',
        ];
    }

    public static function reasonLabel(string $key): string
    {
        return self::reasonOptions()[$key] ?? ucfirst(str_replace('-', ' ', $key));
    }

    /**
     * @param  array{
     *     follow_up_type: string,
     *     branch_id: int,
     *     booking_date: string,
     *     booking_time: string,
     *     follow_up_reason: string,
     *     clinical_notes?: string|null,
     *     send_notification_reminder?: bool
     * }  $payload
     */
    public function createFollowUpBooking(Doctor $doctor, DoctorBooking $sourceBooking, array $payload): DoctorBooking
    {
        $date = Carbon::parse($payload['booking_date'])->startOfDay();
        if ($date->lt(now()->startOfDay())) {
            throw new \InvalidArgumentException('Follow-up date must be today or a future date.');
        }

        $branchId = (int) $payload['branch_id'];
        $timeLabel = $this->formatTimeLabel((string) $payload['booking_time']);
        $isOnline = ($payload['follow_up_type'] ?? 'in-clinic') === 'online';
        $reasonKey = (string) $payload['follow_up_reason'];
        $reasonLabel = self::reasonLabel($reasonKey);
        $clinicalNotes = trim((string) ($payload['clinical_notes'] ?? ''));
        $sendReminder = (bool) ($payload['send_notification_reminder'] ?? false);

        return DB::transaction(function () use (
            $doctor,
            $sourceBooking,
            $date,
            $branchId,
            $timeLabel,
            $isOnline,
            $reasonKey,
            $reasonLabel,
            $clinicalNotes,
            $sendReminder
        ) {
            $booking = DoctorBooking::create([
                'name' => $sourceBooking->name,
                'mobile_number' => $sourceBooking->mobile_number,
                'member_id' => $sourceBooking->member_id,
                'patient_id' => $sourceBooking->patient_id,
                'relationship' => $sourceBooking->relationship,
                'branch_id' => $branchId,
                'hospital_id' => $branchId,
                'doctor_id' => $doctor->id,
                'department_id' => $sourceBooking->department_id,
                'appointment_type' => $isOnline ? 'Online Consultation' : 'In-Clinic',
                'consultation_type' => $isOnline ? 'Online' : 'In-Person',
                'booking_date' => $date->toDateString(),
                'required_time_slots' => [$timeLabel],
                'is_follow_up' => true,
                'follow_up_reason' => $reasonKey,
                'clinical_notes' => $clinicalNotes !== '' ? $clinicalNotes : null,
                'send_notification_reminder' => $sendReminder,
                'reason_of_visit' => $reasonLabel,
                'purpose' => 'Follow-up: '.$reasonLabel,
                'message' => $clinicalNotes !== '' ? $clinicalNotes : 'Follow-up appointment scheduled by doctor',
                'status' => 'confirmed',
                'appointment_status' => DoctorBooking::APPOINTMENT_STATUS_NEW,
                'payment_status' => 'unpaid',
            ]);

            if ($sendReminder && filled($booking->member_id)) {
                $this->sendFollowUpNotification($booking);
            }

            return $booking;
        });
    }

    public function sendFollowUpNotification(DoctorBooking $booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        $booking->loadMissing('doctor');

        $doctorName = trim((string) ($booking->doctor?->name ?? 'your doctor'));
        $appointmentDate = $booking->booking_date?->format('d M Y') ?? 'the scheduled date';
        $timeLabel = collect($booking->required_time_slots ?? [])->first();
        $reasonLabel = self::reasonLabel((string) ($booking->follow_up_reason ?? 'follow-up'));

        $title = 'Follow-up Appointment Booked';
        $body = 'Your follow-up with Dr. '.$doctorName.' is scheduled for '.$appointmentDate
            .($timeLabel ? ' at '.$timeLabel : '')
            .'. Reason: '.$reasonLabel.'.';

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, [
            'type' => 'follow_up_booking',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'doctor_name' => $doctorName,
            'follow_up_date' => $appointmentDate,
            'follow_up_reason' => $reasonLabel,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    protected function formatTimeLabel(string $time24): string
    {
        $time24 = trim($time24);
        if ($time24 === '') {
            return '—';
        }

        try {
            return Carbon::createFromFormat('H:i', strlen($time24) === 5 ? $time24 : substr($time24, 0, 5))
                ->format('h:i A');
        } catch (\Throwable) {
            return $time24;
        }
    }
}
