<?php

namespace App\Livewire\TechnicianAdmin\DiagnosticTestBooking;

use App\Models\DiagnosticTestBookingStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class EditNote extends Component
{
    public $editingNoteId = null;

    public $editingNoteText = '';

    #[On('openEditNoteModal')]
    public function openEditNoteModal($noteId): void
    {
        $note = DiagnosticTestBookingStatus::find($noteId);

        if ($note && $note->notes_by == Auth::id()) {
            $this->editingNoteId = $noteId;
            $this->editingNoteText = $note->notes;
            Flux::modal('edit-note')->show();
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only edit your own notes!');
        }
    }

    public function updateNote(): void
    {
        $this->validate([
            'editingNoteText' => 'required|string|max:1000',
        ]);

        $note = DiagnosticTestBookingStatus::find($this->editingNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->update([
                'notes' => $this->editingNoteText,
            ]);

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
