<?php

namespace App\Livewire\TechnicianAdmin\DiagnosticTestBooking;

use App\Models\DiagnosticTestBookingStatus;
use App\Services\NotificationService;
use App\Services\TechnicianDiagnosticScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AppointmentDetails extends Component
{
    public $id;

    public $diagnosticBooking;

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

    protected function scopeService(): TechnicianDiagnosticScopeService
    {
        return app(TechnicianDiagnosticScopeService::class);
    }

    public function loadData(): void
    {
        $this->diagnosticBooking = $this->scopeService()
            ->scopedBookingsQuery()
            ->with(['member', 'patient', 'diagnosticCenter', 'branch', 'statuses.changedBy', 'statuses.notesBy'])
            ->findOrFail($this->id);

        $this->statuses = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();

        $this->statusHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $this->notesHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderByDesc('created_at')
            ->get();

        $this->notes = $this->diagnosticBooking->notes()->latest()->first();
    }

    public function openAddNoteModal(): void
    {
        $this->dispatch('openAddNoteModal', id: $this->id);
    }

    public function openEditNoteModal($noteId): void
    {
        $this->dispatch('openEditNoteModal', noteId: $noteId);
    }

    public function openDeleteNoteModal($id): void
    {
        $note = DiagnosticTestBookingStatus::find($id);

        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-note')->show();
            $this->deleteNoteId = $id;
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    public function closeDeleteNoteModal(): void
    {
        $this->deleteNoteId = null;
        Flux::modal('delete-note')->close();
    }

    public function deleteNote(): void
    {
        $note = DiagnosticTestBookingStatus::find($this->deleteNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->delete();
            $this->loadData();
            $this->closeDeleteNoteModal();
            $this->dispatch('toast', type: 'success', message: 'Note deleted successfully!');
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    #[On('refreshAppointmentDetails')]
    public function refresh(): void
    {
        $this->loadData();
    }

    public function updateStatusInstant($status): void
    {
        $booking = $this->diagnosticBooking;
        $oldStatus = $booking->status;

        $booking->status = $status;
        $booking->save();

        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::id(),
        ]);

        if ($status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
    }

    protected function sendConfirmationNotification($booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        app(NotificationService::class)->notifyUser((string) $booking->member_id, 'Your Package booking is confirmed!', 'Your diagnostic booking has been confirmed.', [
            'type' => 'package_confirmed',
            'screen' => 'booking_history',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    protected function sendCancellationNotification($booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        app(NotificationService::class)->notifyUser((string) $booking->member_id, 'Your Package booking is cancelled!', 'Your diagnostic booking has been cancelled.', [
            'type' => 'package_cancelled',
            'screen' => 'booking_history',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    public function render()
    {
        return view('livewire.technician-admin.diagnostic-test-booking.appointment-details');
    }
}
