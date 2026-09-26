<?php

namespace App\Services;

use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Modules\Automation\Events\AppointmentRescheduled;
use App\Modules\Automation\Support\AfterCommit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

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
                'cancelled' => $this->sendCancellationNotification($booking),
                default => null,
            };

            $this->dispatchAutomationEvents($booking, $newStatus);
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

    /**
     * Move an existing DoctorBooking to a new date/time. Same row, same id.
     * AppointmentRescheduled is dispatched only after the update commits.
     */
    public function reschedule(DoctorBooking $booking, string $bookingDate, string $timeSlot): DoctorBooking
    {
        $validated = Validator::make(
            [
                'booking_date' => $bookingDate,
                'required_time_slots' => [trim($timeSlot)],
            ],
            [
                'booking_date' => 'required|date|after_or_equal:today',
                'required_time_slots' => 'required|array|min:1',
                'required_time_slots.0' => 'required|string',
            ]
        )->validate();

        $newDate = Carbon::parse($validated['booking_date'])->toDateString();
        $formattedTime = $this->formatAppointmentTime($validated['required_time_slots'][0]);

        $currentDate = $booking->booking_date?->toDateString();
        $currentSlots = is_array($booking->required_time_slots)
            ? array_values($booking->required_time_slots)
            : [];
        $currentTime = isset($currentSlots[0]) ? $this->formatAppointmentTime((string) $currentSlots[0]) : '';
        $oldStatus = (string) ($booking->status ?? '');
        $hospitalId = $booking->hospital_id;

        Log::info('[appointment-rescheduled] Reschedule requested', [
            'appointment_id' => $booking->id,
            'old_booking_date' => $currentDate,
            'old_required_time_slots' => $currentSlots,
            'new_booking_date' => $newDate,
            'new_time' => $formattedTime,
            'old_status' => $oldStatus,
            'hospital_id' => $hospitalId,
        ]);

        if ($currentDate === $newDate && $currentTime === $formattedTime) {
            Log::info('[appointment-rescheduled] No-op; date/time unchanged', [
                'appointment_id' => $booking->id,
            ]);

            return $booking;
        }

        $previousDate = $currentDate;
        $slots = $currentSlots;
        $slots[0] = $formattedTime;
        $bookingId = $booking->id;
        $restoredStatus = $oldStatus === DoctorBooking::STATUS_MISSED
            ? $this->activeLifecycleStatusFromPayment($booking)
            : $oldStatus;

        DB::transaction(function () use ($booking, $newDate, $slots, $restoredStatus, $oldStatus) {
            $booking->booking_date = $newDate;
            $booking->required_time_slots = $slots;
            if ($restoredStatus !== $oldStatus) {
                $booking->status = $restoredStatus;
            }
            DoctorBooking::withoutEvents(fn () => $booking->save());
        });

        $fresh = $booking->fresh() ?? $booking;

        AfterCommit::run(function () use ($fresh, $previousDate, $oldStatus) {
            Log::info('[appointment-rescheduled] Dispatching AppointmentRescheduled after commit', [
                'appointment_id' => $fresh->id,
                'hospital_id' => $fresh->hospital_id,
                'new_booking_date' => $fresh->booking_date?->toDateString(),
                'new_required_time_slots' => $fresh->required_time_slots,
                'old_status' => $oldStatus,
                'status' => $fresh->status ?? $oldStatus,
                'previous_date' => $previousDate,
            ]);
            AppointmentRescheduled::dispatch($fresh, $previousDate);
        });

        if ($fresh->id !== $bookingId) {
            throw new \RuntimeException('Reschedule must keep the same appointment id.');
        }

        return $fresh;
    }

    /**
     * Same lifecycle used at booking create / Razorpay finalize:
     * paid online → confirmed; pay at hospital or unpaid online → pending.
     */
    protected function activeLifecycleStatusFromPayment(DoctorBooking $booking): string
    {
        $paidOnline = (bool) $booking->is_online_payment
            && (string) $booking->payment_status === 'paid';

        return $paidOnline
            ? DoctorBooking::STATUS_CONFIRMED
            : DoctorBooking::STATUS_PENDING;
    }

    protected function formatAppointmentTime(string $timeSlot): string
    {
        $trimmed = trim($timeSlot);

        if ($trimmed === '') {
            throw ValidationException::withMessages([
                'required_time_slots' => ['A valid appointment time is required.'],
            ]);
        }

        try {
            return Carbon::parse($trimmed)->format('g:i A');
        } catch (\Throwable) {
            throw ValidationException::withMessages([
                'required_time_slots' => ['A valid appointment time is required.'],
            ]);
        }
    }

    public function updateAppointmentStatus(
        DoctorBooking $booking,
        string $newAppointmentStatus,
        ?int $changedBy = null,
        bool $sendNotifications = true
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
                $sendNotifications
                && $newAppointmentStatus === DoctorBooking::APPOINTMENT_STATUS_COMPLETED
                && $oldBookingStatus !== 'completed'
            ) {
                $this->sendReviewNotification($booking);
            }

            $this->dispatchAutomationEvents($booking, $booking->status, $newAppointmentStatus);
        }

        return $booking->fresh();
    }

    public function updateAppointmentStatusWithLink(
        DoctorBooking $booking,
        string $newAppointmentStatus,
        ?string $consultationLink = null,
        ?int $changedBy = null
    ): DoctorBooking {
        $oldLink = trim((string) ($booking->online_consultation_link ?? ''));
        $oldAppointmentStatus = $booking->appointment_status ?: DoctorBooking::APPOINTMENT_STATUS_NEW;
        $newLink = trim((string) ($consultationLink ?? ''));

        if ($newLink !== '') {
            $booking->online_consultation_link = $newLink;
            $booking->save();
        }

        $booking = $this->updateAppointmentStatus($booking, $newAppointmentStatus, $changedBy);
        $booking->refresh();

        $linkChanged = $newLink !== '' && $newLink !== $oldLink;
        $scheduledToCheckedIn = $newAppointmentStatus === DoctorBooking::APPOINTMENT_STATUS_CHECKED_IN
            && $oldAppointmentStatus === DoctorBooking::APPOINTMENT_STATUS_NEW;

        if (filled($booking->online_consultation_link)) {
            if ($scheduledToCheckedIn) {
                $this->sendOnlineConsultationLinkNotification($booking, checkedIn: true);
            } elseif ($linkChanged) {
                $this->sendOnlineConsultationLinkNotification($booking);
            }
        }

        return $booking;
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

    public function sendOnlineConsultationLinkNotification(DoctorBooking $booking, bool $checkedIn = false): void
    {
        if (! $booking->member_id || ! $booking->doctor_id || ! filled($booking->online_consultation_link)) {
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
        $doctorName = trim((string) ($booking->doctor?->name ?? 'your doctor'));

        if ($checkedIn) {
            $title = 'Your online consultation is ready to join';
            $body = 'Dr. '.$doctorName.' has started your online session. Tap to join your consultation'.$timeText.'.';
        } else {
            $title = 'Your online consultation link is ready';
            $body = 'Join your online consultation with Dr. '.$doctorName.' on '.$appointmentDate.$timeText.'.';
        }

        $data = [
            'type' => $checkedIn ? 'online_consultation_started' : 'online_consultation_link',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'appointment',
            'booking_id' => (string) $booking->id,
            'consultation_link' => $booking->online_consultation_link,
            'url' => $booking->online_consultation_link,
            'route' => '/booking-history',
        ];

        $this->notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    protected function dispatchAutomationEvents(
        DoctorBooking $booking,
        string $bookingStatus,
        ?string $appointmentStatus = null
    ): void {
        $appointmentStatus ??= $booking->appointment_status;

        match (true) {
            $bookingStatus === 'cancelled'
                || $appointmentStatus === DoctorBooking::APPOINTMENT_STATUS_CANCELLED
                => event(new \App\Modules\Automation\Events\AppointmentCancelled($booking->fresh())),
            $appointmentStatus === DoctorBooking::APPOINTMENT_STATUS_COMPLETED
                || $bookingStatus === 'completed'
                => event(new \App\Modules\Automation\Events\AppointmentCompleted($booking->fresh())),
            default => null,
        };
    }
}
