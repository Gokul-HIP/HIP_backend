<?php

namespace App\Livewire\Admin\DoctorBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\DoctorBookingStatus;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;

class AddNote extends Component
{
    public $doctor_booking_id;
    public $note;

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id)
    {
        $this->doctor_booking_id = $id;
        $this->note = '';
        Flux::modal('add-note')->show();
    }

    public function addNote()
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        DoctorBookingStatus::create([
            'doctor_booking_id' => $this->doctor_booking_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);

        $this->closeModal();

        $this->dispatch('toast', type: 'success', message: 'Note added successfully!');
        $this->dispatch('refreshAppointmentDetails');
    }

    public function closeModal()
    {
        $this->reset(['note', 'doctor_booking_id']);
        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.add-note');
    }
}