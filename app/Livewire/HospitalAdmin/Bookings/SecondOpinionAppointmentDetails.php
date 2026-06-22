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

class SecondOpinionAppointmentDetails extends Component
{
    public $id;
    public $secondOpinion;
    public $statuses;
    public $statusHistory;
    public $notesHistory;
    public $notes;
    public $deleteNoteId;

    public function mount($id): void
    {
        $this->id = $id;
        $this->loadData();
    }

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function scopedBookingQuery()
    {
        return SecondOpinion::query()
            ->whereIn('branch_id', $this->organizationHospitalIds());
    }

    public function loadData(): void
    {
        $this->secondOpinion = $this->scopedBookingQuery()
            ->with(['member', 'doctor', 'branch', 'speciality', 'statuses.changedBy', 'statuses.notesBy'])
            ->find($this->id);

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
            ->get();

        $this->notesHistory = $this->secondOpinion->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->secondOpinion->notes()->latest()->first();
    }

    public function openAddNoteModal(): void
    {
        $this->dispatch('openHealthcareSecondOpinionNoteModal', id: $this->id);
    }

    public function openEditNoteModal($noteId): void
    {
        $this->dispatch('openEditHealthcareSecondOpinionNoteModal', noteId: $noteId);
    }

    public function openDeleteNoteModal($id): void
    {
        $note = SecondOpinionStatus::find($id);

        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-second-opinion-note')->show();
            $this->deleteNoteId = $id;
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    public function closeDeleteNoteModal(): void
    {
        $this->deleteNoteId = null;
        Flux::modal('delete-second-opinion-note')->close();
    }

    public function deleteNote(): void
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

    #[On('refreshHealthcareSecondOpinionDetails')]
    public function refresh(): void
    {
        $this->loadData();
    }

    public function updateStatusInstant($status): void
    {
        $booking = $this->scopedBookingQuery()->find($this->id);

        if (! $booking) {
            return;
        }

        $oldStatus = $booking->status;
        $booking->status = $status;
        $booking->save();

        SecondOpinionStatus::create([
            'second_opinion_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::id(),
        ]);

        if ($status === 'completed') {
            $this->sendReviewNotification($booking);
        }

        if ($status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->loadData();
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

    public function render()
    {
        return view('livewire.hospital-admin.bookings.second-opinion-appointment-details');
    }
}
