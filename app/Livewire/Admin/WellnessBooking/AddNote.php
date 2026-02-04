<?php

namespace App\Livewire\Admin\WellnessBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\WellnessBookingStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AddNote extends Component
{
    public $wellness_booking_id;
    public $note;

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id)
    {
        $this->wellness_booking_id = $id;
        $this->note = '';
        Flux::modal('add-note')->show();
    }

    public function addNote()
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        WellnessBookingStatus::create([
            'wellness_booking_id' => $this->wellness_booking_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);
        
        $this->dispatch('refreshAppointmentDetails');
        $this->closeModal();
        // $this->dispatch('toast', type: 'success', message: 'Note added successfully!');
    }

    public function closeModal()
    {
        $this->reset(['note', 'wellness_booking_id']);
        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.wellness-booking.add-note');
    }
}

