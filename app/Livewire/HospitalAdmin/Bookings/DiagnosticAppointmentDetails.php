<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DiagnosticAppointmentDetails extends Component
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

    protected function organizationDiagnosticIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->whereNotNull('diagnostic_center_id')
            ->pluck('diagnostic_center_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function scopedBookingQuery()
    {
        return DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $this->organizationDiagnosticIds());
    }

    public function loadData(): void
    {
        $this->diagnosticBooking = $this->scopedBookingQuery()
            ->with('member', 'diagnosticCenter', 'statuses.changedBy', 'statuses.notesBy')
            ->findOrFail($this->id);

        $this->statuses = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();

        $this->statusHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->latest()
            ->get();

        $this->notesHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
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
        $booking = $this->scopedBookingQuery()->find($this->id);

        if (!$booking) {
            return;
        }

        $oldStatus = $booking->status;
        $booking->status = $status;
        $booking->save();

        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::id(),
        ]);

        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
    }

    public function render()
    {
        return view('livewire.hospital-admin.bookings.diagnostic-appointment-details');
    }
}
