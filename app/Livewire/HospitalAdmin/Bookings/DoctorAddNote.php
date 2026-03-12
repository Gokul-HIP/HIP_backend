<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DoctorAddNote extends Component
{
    public $doctor_booking_id;
    public $note;

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function findScopedBooking(?int $id): ?DoctorBooking
    {
        if (!$id) {
            return null;
        }

        return DoctorBooking::query()
            ->whereIn('hospital_id', $this->organizationHospitalIds())
            ->find($id);
    }

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (!$this->findScopedBooking((int) $id)) {
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

        if (!$this->findScopedBooking((int) $this->doctor_booking_id)) {
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
