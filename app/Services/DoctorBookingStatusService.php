<?php

namespace App\Services;

use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use Illuminate\Support\Facades\Auth;

class DoctorBookingStatusService
{
    public function __construct(
        protected NotificationService $notificationService
    ) {}

    public function updateBookingStatus(
        DoctorBooking $booking,
        string $newStatus,
        ?int $changedBy = null,
        ?string $note = null
    ): DoctorBooking {
        $changedBy = $changedBy ?? Auth::id();
        $oldStatus = (string) ($booking->status ?? 'pending');

        if ($newStatus === $oldStatus && empty($note)) {
            return $booking;
        }

        $booking->status = $newStatus;

        if ($newStatus === 'confirmed') {
            $booking->appointment_status = DoctorBooking::APPOINTMENT_STATUS_NEW;
        } elseif ($newStatus === 'completed') {
            $booking->appointment_status = DoctorBooking::APPOINTMENT_STATUS_COMPLETED;
        } elseif ($newStatus === 'cancelled') {
            $booking->appointment_status = DoctorBooking::APPOINTMENT_STATUS_CANCELLED;
        }

        $booking->save();

        if ($newStatus !== $oldStatus) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $booking->id,
                'from_status' => $oldStatus,
                'to_status' => $newStatus,
                'changed_by' => $changedBy,
            ]);

            match ($newStatus) {
                'completed' => $this->sendReviewNotification($booking),
                'confirmed' => $this->sendConfirmationNotification($booking),
                'cancelled' => $this->sendCancellationNotification($booking),
                default => null,
            };
        }

        if ($note) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $booking->id,
                'notes' => $note,
                'notes_by' => $changedBy,
            ]);
        }

        return $booking->fresh();
    }

    public function updateAppointmentStatus(
        DoctorBooking $booking,
        string $newAppointmentStatus,
        ?int $changedBy = null
    ): DoctorBooking {
        $changedBy = $changedBy ?? Auth::id();
        $oldAppointmentStatus = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;
        $oldBookingStatus = (string) ($booking->status ?? 'pending');

        $booking->appointment_status = $newAppointmentStatus;

        if ($newAppointmentStatus === DoctorBooking::APPOINTMENT_STATUS_COMPLETED) {
            $booking->status = 'completed';
        } elseif ($newAppointmentStatus === DoctorBooking::APPOINTMENT_STATUS_CANCELLED) {
            $booking->status = 'cancelled';
        } elseif ($booking->status === 'pending') {
            $booking->status = 'confirmed';
        }

        $booking->save();

        if ($oldAppointmentStatus !== $newAppointmentStatus) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $booking->id,
                'from_status' => $oldBookingStatus,
                'to_status' => $booking->status,
                'changed_by' => $changedBy,
                'notes' => sprintf(
                    'Appointment status changed from %s to %s',
                    DoctorBooking::appointmentStatusOptions()[$oldAppointmentStatus] ?? $oldAppointmentStatus,
                    DoctorBooking::appointmentStatusOptions()[$newAppointmentStatus] ?? $newAppointmentStatus
                ),
            ]);

            if (
                $newAppointmentStatus === DoctorBooking::APPOINTMENT_STATUS_COMPLETED
                && $oldBookingStatus !== 'completed'
            ) {
                $this->sendReviewNotification($booking);
            }
        }

        return $booking->fresh();
    }

    public function markConfirmed(DoctorBooking $booking): DoctorBooking
    {
        $booking->status = 'confirmed';
        $booking->appointment_status = DoctorBooking::APPOINTMENT_STATUS_NEW;
        $booking->save();

        return $booking;
    }

    public function sendReviewNotification(DoctorBooking $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing(['doctor', 'department']);

        $doctorName = trim((string) ($booking->doctor?->name ?? ''));
        $doctorSpeciality = trim((string) (
            $booking->department?->name
            ?: (($booking->doctor?->speciality_names ?? '-') !== '-'
                ? $booking->doctor?->speciality_names
                : '')
        ));

        $title = 'How was your appointment?';
        $body = 'Please review the doctor ('.$doctorName.') for your recent appointment.';

        $data = [
            'type' => 'review_popup',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'doctor_name' => $doctorName,
            'doctor_speciality' => $doctorSpeciality,
            'department_name' => $booking->department?->name,
            'doctor_image' => $booking->doctor?->doctor_image
                ? url('storage/doctor/' . ltrim((string) $booking->doctor->doctor_image, '/'))
                : null,
            'url' => '/review/'.$booking->doctor_id,
            'route' => '/review/'.$booking->doctor_id,
        ];

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    public function sendConfirmationNotification(DoctorBooking $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');

        $appointmentDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $appointmentTime = null;
        if (is_array($booking->required_time_slots) && count($booking->required_time_slots) > 0) {
            $appointmentTime = $booking->required_time_slots[0];
        }

        $timeText = $appointmentTime ? ' at '.$appointmentTime : '';

        $title = 'Your appointment is confirmed!';
        $body = 'Your appointment with the doctor ('.$booking->doctor->name.') has been confirmed for '.$appointmentDate.$timeText.'.';

        $data = [
            'type' => 'appointment_confirmation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    public function sendCancellationNotification(DoctorBooking $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');

        $appointmentDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $appointmentTime = null;
        if (is_array($booking->required_time_slots) && count($booking->required_time_slots) > 0) {
            $appointmentTime = $booking->required_time_slots[0];
        }

        $timeText = $appointmentTime ? ' at '.$appointmentTime : '';

        $title = 'Your appointment is cancelled!';
        $body = 'Your appointment with the doctor ('.$booking->doctor->name.') has been cancelled for '.$appointmentDate.$timeText.'.';

        $data = [
            'type' => 'appointment_cancellation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }
}
