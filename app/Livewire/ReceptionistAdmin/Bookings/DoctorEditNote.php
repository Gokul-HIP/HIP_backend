<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\DoctorBookingStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DoctorEditNote extends Component
{
    use ScopesReceptionistBookings;

    public $editingNoteId = null;
    public $editingNoteText = '';

    #[On('openEditNoteModal')]
    public function openEditNoteModal($noteId): void
    {
        $note = DoctorBookingStatus::find($noteId);

        if (
            $note
            && $this->scopeService()->doctorBookingBelongsToScope((int) $note->doctor_booking_id)
            && $note->notes_by == Auth::id()
        ) {
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

        $note = DoctorBookingStatus::find($this->editingNoteId);

        if (
            $note
            && $this->scopeService()->doctorBookingBelongsToScope((int) $note->doctor_booking_id)
            && $note->notes_by == Auth::id()
        ) {
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
        return view('livewire.admin.doctor-booking.edit-note');
    }
}
