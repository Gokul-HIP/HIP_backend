<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DiagnosticEditNote extends Component
{
    public $editingNoteId = null;
    public $editingNoteText = '';

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

    protected function noteBelongsToOrganization(DiagnosticTestBookingStatus $note): bool
    {
        return DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $this->organizationDiagnosticIds())
            ->whereKey($note->diagnostic_test_booking_id)
            ->exists();
    }

    #[On('openEditNoteModal')]
    public function openEditNoteModal($noteId): void
    {
        $note = DiagnosticTestBookingStatus::find($noteId);

        if ($note && $this->noteBelongsToOrganization($note) && $note->notes_by == Auth::id()) {
            $this->editingNoteId = $noteId;
            $this->editingNoteText = $note->notes;
            Flux::modal('edit-note')->show();
            return;
        }

        $this->dispatch('toast', type: 'error', message: 'You can only edit your own notes!');
    }

    public function updateNote(): void
    {
        $this->validate([
            'editingNoteText' => 'required|string|max:1000',
        ]);

        $note = DiagnosticTestBookingStatus::find($this->editingNoteId);

        if ($note && $this->noteBelongsToOrganization($note) && $note->notes_by == Auth::id()) {
            $note->update(['notes' => $this->editingNoteText]);
            $this->dispatch('refreshAppointmentDetails');
            $this->closeEditNoteModal();
            $this->dispatch('toast', type: 'success', message: 'Note updated successfully!');
        }
    }

    public function closeEditNoteModal(): void
    {
        $this->editingNoteId = null;
        $this->editingNoteText = '';
        Flux::modal('edit-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.diagnostic-test-booking.edit-note');
    }
}
