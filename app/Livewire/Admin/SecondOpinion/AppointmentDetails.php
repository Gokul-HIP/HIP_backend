<?php

namespace App\Livewire\Admin\SecondOpinion;

use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use App\Services\NotificationService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AppointmentDetails extends Component
{
    public $id;

    public $secondOpinion;

    public $statuses;

    public $statusHistory;

    public $notesHistory;

    public $notes;

    public $deleteNoteId;

    public function mount($id)
    {
        $this->id = $id;
        $this->loadData();
    }

    public function loadData()
    {
        $this->secondOpinion = SecondOpinion::with([
            'member',
            'doctor',
            'branch',
            'speciality',
            'statuses.changedBy',
            'statuses.notesBy',
        ])->find($this->id);

        if (! $this->secondOpinion) {
            return;
        }

        $this->statuses = $this->secondOpinion->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();

        $this->statusHistory = $this->secondOpinion->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->latest()
            ->get();

        $this->notesHistory = $this->secondOpinion->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->secondOpinion->notes()->latest()->first();
    }

    public function openAddNoteModal()
    {
        $this->dispatch('openAddSecondOpinionNoteModal', id: $this->id);
    }

    public function openEditNoteModal($noteId)
    {
        $this->dispatch('openEditSecondOpinionNoteModal', noteId: $noteId);
    }

    public function openDeleteNoteModal($id)
    {
        $note = SecondOpinionStatus::find($id);

        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-second-opinion-note')->show();
            $this->deleteNoteId = $id;
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    public function closeDeleteNoteModal()
    {
        $this->deleteNoteId = null;
        Flux::modal('delete-second-opinion-note')->close();
    }

    public function deleteNote()
    {
        $note = SecondOpinionStatus::find($this->deleteNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->delete();
            $this->loadData();
            $this->closeDeleteNoteModal();
            $this->dispatch('toast', type: 'success', message: 'Note deleted successfully!');
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    #[On('refreshSecondOpinionDetails')]
    public function refresh()
    {
        $this->loadData();
    }

    public function updateStatusInstant($status)
    {
        $secondOpinion = SecondOpinion::find($this->id);

        if (! $secondOpinion) {
            return;
        }

        $oldStatus = $secondOpinion->status;

        $secondOpinion->status = $status;
        $secondOpinion->save();

        SecondOpinionStatus::create([
            'second_opinion_id' => $secondOpinion->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::user()->id,
        ]);

        if ($status === 'completed') {
            $this->sendReviewNotification($secondOpinion);
        }

        if ($status === 'confirmed') {
            $this->sendConfirmationNotification($secondOpinion);
        }

        if ($status === 'cancelled') {
            $this->sendCancellationNotification($secondOpinion);
        }

        $this->loadData();

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

    public function render()
    {
        return view('livewire.admin.second-opinion.appointment-details');
    }
}
