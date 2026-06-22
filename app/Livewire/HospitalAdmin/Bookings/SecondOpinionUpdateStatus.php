<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Hospital;
use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use App\Services\NotificationService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SecondOpinionUpdateStatus extends Component
{
    public $id;
    public $status;
    public $note;

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function findScopedBooking(?int $id): ?SecondOpinion
    {
        if (! $id) {
            return null;
        }

        return SecondOpinion::query()
            ->whereIn('branch_id', $this->organizationHospitalIds())
            ->find($id);
    }

    #[On('openHealthcareSecondOpinionStatusModal')]
    public function openUpdateStatusModal($id): void
    {
        $booking = $this->findScopedBooking((int) $id);

        if (! $booking) {
            return;
        }

        $this->id = $booking->id;
        $this->status = $booking->status;
        $this->note = '';
        Flux::modal('update-second-opinion-status')->show();
    }

    public function updateStatus(): void
    {
        $booking = $this->findScopedBooking((int) $this->id);

        if (! $booking) {
            $this->closeModal();

            return;
        }

        $oldStatus = $booking->status;
        $booking->status = $this->status;
        $booking->save();

        if ($this->status !== $oldStatus) {
            SecondOpinionStatus::create([
                'second_opinion_id' => $booking->id,
                'from_status' => $oldStatus,
                'to_status' => $this->status,
                'changed_by' => Auth::id(),
            ]);

            if ($this->status === 'completed') {
                $this->sendReviewNotification($booking);
            }

            if ($this->status === 'confirmed') {
                $this->sendConfirmationNotification($booking);
            }

            if ($this->status === 'cancelled') {
                $this->sendCancellationNotification($booking);
            }
        }

        if ($this->note) {
            SecondOpinionStatus::create([
                'second_opinion_id' => $booking->id,
                'notes' => $this->note,
                'notes_by' => Auth::id(),
            ]);
        }

        $this->dispatch('refreshHealthcareSecondOpinions');
        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . ($booking->patient_name ?? 'patient') . ' successfully!');
    }

    protected function sendReviewNotification(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing(['doctor', 'speciality']);
        $notificationService = app(NotificationService::class);
        $doctorName = trim((string) ($booking->doctor?->name ?? ''));

        $notificationService->notifyUser((string) $booking->member_id, 'How was your second opinion?', 'Please review the doctor (' . $doctorName . ') for your recent second opinion consultation.', [
            'type' => 'review_popup',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'second_opinion',
            'booking_id' => (string) $booking->id,
            'doctor_name' => $doctorName,
            'url' => '/review/' . $booking->doctor_id,
            'route' => '/review/' . $booking->doctor_id,
        ]);
    }

    protected function sendConfirmationNotification(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');
        $notificationService = app(NotificationService::class);
        $appointmentDate = $booking->preferred_date ? $booking->preferred_date->format('d M Y') : 'your scheduled date';
        $appointmentTime = is_array($booking->preferred_time_slots) && count($booking->preferred_time_slots) > 0
            ? $booking->preferred_time_slots[0]
            : null;
        $timeText = $appointmentTime ? ' at ' . $appointmentTime : '';

        $notificationService->notifyUser((string) $booking->member_id, 'Your second opinion is confirmed!', 'Your second opinion with Dr. ' . ($booking->doctor->name ?? 'doctor') . ' has been confirmed for ' . $appointmentDate . $timeText . '.', [
            'type' => 'appointment_confirmation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'second_opinion',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    protected function sendCancellationNotification(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');
        $notificationService = app(NotificationService::class);
        $appointmentDate = $booking->preferred_date ? $booking->preferred_date->format('d M Y') : 'your scheduled date';
        $appointmentTime = is_array($booking->preferred_time_slots) && count($booking->preferred_time_slots) > 0
            ? $booking->preferred_time_slots[0]
            : null;
        $timeText = $appointmentTime ? ' at ' . $appointmentTime : '';

        $notificationService->notifyUser((string) $booking->member_id, 'Your second opinion is cancelled!', 'Your second opinion with Dr. ' . ($booking->doctor->name ?? 'doctor') . ' has been cancelled for ' . $appointmentDate . $timeText . '.', [
            'type' => 'appointment_cancellation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'second_opinion',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    public function closeModal(): void
    {
        $this->reset(['status', 'id', 'note']);
        Flux::modal('update-second-opinion-status')->close();
    }

    public function render()
    {
        return view('livewire.hospital-admin.bookings.second-opinion-update-status');
    }
}
