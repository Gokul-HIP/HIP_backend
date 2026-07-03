<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\DoctorBookingStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DoctorAddNote extends Component
{
    use ScopesReceptionistBookings;

    public $doctor_booking_id;
    public $note;

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (! $this->scopeService()->findDoctorBooking((int) $id)) {
            return;
        }

        $this->doctor_booking_id = (int) $id;
        $this->note = '';
        Flux::modal('add-note')->show();
    }

    public function addNote(): void
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        if (! $this->scopeService()->findDoctorBooking((int) $this->doctor_booking_id)) {
            $this->closeModal();

            return;
        }

        DoctorBookingStatus::create([
            'doctor_booking_id' => $this->doctor_booking_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Note added successfully!');
        $this->dispatch('refreshAppointmentDetails');
    }

    public function closeModal(): void
    {
        $this->reset(['note', 'doctor_booking_id']);
        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.add-note');
    }
}
