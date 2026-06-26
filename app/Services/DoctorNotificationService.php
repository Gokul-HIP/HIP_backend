<?php

namespace App\Services;

use App\Events\NewDoctorNotification;
use App\Models\DoctorBooking;
use App\Models\Notification;
use App\Support\CurrentDoctor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class DoctorNotificationService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Persist a doctor web notification and broadcast it in real time.
     * Does not send FCM — mobile patient flow is unchanged.
     */
    public function notifyDoctor(
        string $doctorId,
        string $title,
        string $body,
        array $data = []
    ): ?Notification {
        $doctorUserId = $this->resolveDoctorUserId($doctorId);

        if (! $doctorUserId) {
            Log::warning('Doctor notification skipped: HIP user not found', [
                'doctor_id' => $doctorId,
            ]);

            return null;
        }

        $notification = $this->notificationService->storeNotification(
            $doctorUserId,
            $title,
            $body,
            $data
        );

        event(new NewDoctorNotification($notification, $doctorId));

        return $notification;
    }

    public function notifyDoctorOfNewBooking(DoctorBooking $booking): ?Notification
    {
        $booking->loadMissing(['patient', 'doctor']);

        $doctorId = (string) $booking->doctor_id;
        if ($doctorId === '') {
            return null;
        }

        $patientName = trim(collect([
            $booking->patient?->first_name,
            $booking->patient?->last_name,
        ])->filter()->join(' '));

        if ($patientName === '') {
            $patientName = trim((string) ($booking->name ?: 'Patient'));
        }

        $bookingDate = $booking->booking_date?->format('d M Y');
        $slots = collect($booking->required_time_slots ?? [])->filter()->values();
        $timeLabel = match ($slots->count()) {
            0 => null,
            1 => (string) $slots->first(),
            default => $slots->first().' - '.$slots->last(),
        };

        $title = 'New Appointment Booked';
        $body = $patientName.' booked an appointment with you'
            .($bookingDate ? ' for '.$bookingDate : '')
            .($timeLabel ? ' at '.$timeLabel : '')
            .'.';

        $data = [
            'type' => 'doctor_appointment_booked',
            'context' => 'appointment',
            'screen' => 'my_appointments',
            'booking_id' => (string) $booking->id,
            'doctor_id' => $doctorId,
            'patient_name' => $patientName,
            'appointment_date' => $bookingDate,
            'appointment_time' => $timeLabel,
            'url' => '/doctor/my-appointment',
            'route' => '/doctor/my-appointment',
        ];

        return $this->notifyDoctor($doctorId, $title, $body, $data);
    }

    public function resolveDoctorUserId(string $doctorId): ?string
    {
        return CurrentDoctor::primaryLinkedUserId($doctorId);
    }

    public static function applyDoctorPortalScope(Builder $query): Builder
    {
        return $query->where('data->type', 'like', 'doctor_%');
    }
}
