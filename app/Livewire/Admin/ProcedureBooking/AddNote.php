<?php

namespace App\Livewire\Admin\ProcedureBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\ProcedureBookingStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AddNote extends Component
{
    public $procedure_booking_id;
    public $note;

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id)
    {
        $this->procedure_booking_id = $id;
        $this->note = '';
        Flux::modal('add-note')->show();
    }

    public function addNote()
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        ProcedureBookingStatus::create([
            'procedure_booking_id' => $this->procedure_booking_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);
        
        $this->dispatch('refreshAppointmentDetails');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->reset(['note', 'procedure_booking_id']);
        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.procedure-booking.add-note');
    }
}

