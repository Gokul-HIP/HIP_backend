<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Hospital;
use App\Models\ProcedureBooking;
use App\Models\ProcedureBookingStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ProcedureAppointmentDetails extends Component
{
    public $id;
    public $procedureBooking;
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
        return ProcedureBooking::query()
            ->whereIn('hospital_id', $this->organizationHospitalIds());
    }

    public function loadData(): void
    {
        $this->procedureBooking = $this->scopedBookingQuery()
            ->with('member', 'hospital', 'procedure', 'statuses.changedBy', 'statuses.notesBy')
            ->findOrFail($this->id);

        $this->statuses = $this->procedureBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->with('changedBy')
            ->latest()
            ->first();

        $this->statusHistory = $this->procedureBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->latest()
            ->get();

        $this->notesHistory = $this->procedureBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->procedureBooking->notes()->latest()->first();
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
        $note = ProcedureBookingStatus::find($id);

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
        $note = ProcedureBookingStatus::find($this->deleteNoteId);

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
        $procedureBooking = $this->scopedBookingQuery()->find($this->id);

        if (!$procedureBooking) {
            return;
        }

        $oldStatus = $procedureBooking->status;
        $procedureBooking->status = $status;
        $procedureBooking->save();

        ProcedureBookingStatus::create([
            'procedure_booking_id' => $procedureBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::id(),
        ]);

        $this->loadData();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $procedureBooking->name . ' successfully!');
    }

    public function render()
    {
        return view('livewire.hospital-admin.bookings.procedure-appointment-details');
    }
}
