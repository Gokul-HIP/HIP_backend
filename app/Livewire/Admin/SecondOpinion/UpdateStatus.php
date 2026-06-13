<?php

namespace App\Livewire\Admin\SecondOpinion;

use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use App\Services\NotificationService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class UpdateStatus extends Component
{
    public $id;

    public $status;

    public $note;

    #[On('openUpdateSecondOpinionStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = SecondOpinion::find($this->id)?->status ?? 'pending';
        $this->note = '';
        Flux::modal('update-second-opinion-status')->show();
    }

    public function updateStatus()
    {
        $secondOpinion = SecondOpinion::find($this->id);

        if (! $secondOpinion) {
            $this->closeModal();

            return;
        }

        $oldStatus = $secondOpinion->status;

        $secondOpinion->status = $this->status;
        $secondOpinion->save();

        if ($this->status !== $oldStatus) {
            SecondOpinionStatus::create([
                'second_opinion_id' => $secondOpinion->id,
                'from_status' => $oldStatus,
                'to_status' => $this->status,
                'changed_by' => Auth::id(),
            ]);

            if ($this->status === 'completed') {
                $this->sendReviewNotification($secondOpinion);
            }

            if ($this->status === 'confirmed') {
                $this->sendConfirmationNotification($secondOpinion);
            }

            if ($this->status === 'cancelled') {
                $this->sendCancellationNotification($secondOpinion);
            }
        }

        if ($this->note) {
            SecondOpinionStatus::create([
                'second_opinion_id' => $secondOpinion->id,
                'notes' => $this->note,
                'notes_by' => Auth::id(),
            ]);
        }

        $this->dispatch('refreshSecondOpinions');
        $this->closeModal();

        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . ($secondOpinion->patient_name ?? 'patient') . ' successfully!');
    }

    protected function sendReviewNotification(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing(['doctor', 'speciality']);

        $notificationService = app(NotificationService::class);

        $doctorName = trim((string) ($booking->doctor?->name ?? ''));
        $doctorSpeciality = trim((string) (
            $booking->speciality?->name
            ?: (($booking->doctor?->speciality_names ?? '-') !== '-'
                ? $booking->doctor?->speciality_names
                : '')
        ));

        $title = 'How was your second opinion?';
        $body = 'Please review the doctor (' . $doctorName . ') for your recent second opinion consultation.';

        $data = [
            'type' => 'review_popup',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'second_opinion',
            'booking_id' => (string) $booking->id,
            'doctor_name' => $doctorName,
            'doctor_speciality' => $doctorSpeciality,
            'department_name' => $booking->speciality?->name,
            'doctor_image' => $booking->doctor?->doctor_image
                ? url('storage/doctor/' . ltrim((string) $booking->doctor->doctor_image, '/'))
                : null,
            'url' => '/review/' . $booking->doctor_id,
            'route' => '/review/' . $booking->doctor_id,
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    protected function sendConfirmationNotification(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');

        $notificationService = app(NotificationService::class);

        $appointmentDate = $booking->preferred_date
            ? $booking->preferred_date->format('d M Y')
            : 'your scheduled date';

        $appointmentTime = null;
        if (is_array($booking->preferred_time_slots) && count($booking->preferred_time_slots) > 0) {
            $appointmentTime = $booking->preferred_time_slots[0];
        }

        $timeText = $appointmentTime ? ' at ' . $appointmentTime : '';

        $title = 'Your second opinion is confirmed!';
        $body = 'Your second opinion with Dr. ' . ($booking->doctor->name ?? 'doctor') . ' has been confirmed for ' . $appointmentDate . $timeText . '.';

        $data = [
            'type' => 'appointment_confirmation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'second_opinion',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    protected function sendCancellationNotification(SecondOpinion $booking): void
    {
        if (! $booking->member_id || ! $booking->doctor_id) {
            return;
        }

        $booking->loadMissing('doctor');

        $notificationService = app(NotificationService::class);

        $appointmentDate = $booking->preferred_date
            ? $booking->preferred_date->format('d M Y')
            : 'your scheduled date';

        $appointmentTime = null;
        if (is_array($booking->preferred_time_slots) && count($booking->preferred_time_slots) > 0) {
            $appointmentTime = $booking->preferred_time_slots[0];
        }

        $timeText = $appointmentTime ? ' at ' . $appointmentTime : '';

        $title = 'Your second opinion is cancelled!';
        $body = 'Your second opinion with Dr. ' . ($booking->doctor->name ?? 'doctor') . ' has been cancelled for ' . $appointmentDate . $timeText . '.';

        $data = [
            'type' => 'appointment_cancellation',
            'entity_type' => 'doctor',
            'entity_id' => (string) $booking->doctor_id,
            'booking_type' => 'second_opinion',
            'booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    public function closeModal()
    {
        $this->status = '';
        $this->id = null;
        $this->reset(['status', 'id', 'note']);
        Flux::modal('update-second-opinion-status')->close();
    }

    public function render()
    {
        return view('livewire.admin.second-opinion.update-status');
    }
}
