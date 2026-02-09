<?php

namespace App\Livewire\Admin\CaregiverBooking;

use Livewire\Component;
use App\Models\CaregiverBookingStatus;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;
use Livewire\Attributes\On;

class EditNote extends Component
{
    public $noteId;
    public $noteText;
    public $notes;
    public $editingNoteId = null;
    public $editingNoteText = '';

    #[On('openEditNoteModal')]
    public function openEditNoteModal($noteId)
    {
        $note = CaregiverBookingStatus::find($noteId);

        if ($note && $note->notes_by == Auth::id()) {
            $this->editingNoteId = $noteId;
            $this->editingNoteText = $note->notes;
            Flux::modal('edit-note')->show();
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only edit your own notes!');
        }
    }

    public function updateNote()
    {
        $this->validate([
            'editingNoteText' => 'required|string|max:1000'
        ]);

        $note = CaregiverBookingStatus::find($this->editingNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->update([
                'notes' => $this->editingNoteText,
            ]);

            $this->dispatch('refreshAppointmentDetails');
            $this->closeEditNoteModal();

            $this->dispatch('toast', type: 'success', message: 'Note updated successfully!');
        }
    }

    public function closeEditNoteModal()
    {
        $this->editingNoteId = null;
        $this->editingNoteText = '';
        Flux::modal('edit-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.caregiver-booking.edit-note');
    }
}
