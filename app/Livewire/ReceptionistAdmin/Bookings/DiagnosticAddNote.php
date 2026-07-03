<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\DiagnosticTestBookingStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DiagnosticAddNote extends Component
{
    use ScopesReceptionistBookings;

    public $diagnostic_test_booking_id;
    public $note;

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (! $this->scopeService()->findDiagnosticBooking((int) $id)) {
            return;
        }

        $this->diagnostic_test_booking_id = (int) $id;
        $this->note = '';
        Flux::modal('add-note')->show();
    }

    public function addNote(): void
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        if (! $this->scopeService()->findDiagnosticBooking((int) $this->diagnostic_test_booking_id)) {
            $this->closeModal();

            return;
        }

        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $this->diagnostic_test_booking_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Note added successfully!');
        $this->dispatch('refreshAppointmentDetails');
    }

    public function closeModal(): void
    {
        $this->reset(['note', 'diagnostic_test_booking_id']);
        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.diagnostic-test-booking.add-note');
    }
}
