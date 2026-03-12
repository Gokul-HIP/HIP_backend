<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DiagnosticAddNote extends Component
{
    public $diagnostic_test_booking_id;
    public $note;

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

    protected function findScopedBooking(?int $id): ?DiagnosticTestBooking
    {
        if (!$id) {
            return null;
        }

        return DiagnosticTestBooking::query()
            ->whereIn('diagnostic_center_id', $this->organizationDiagnosticIds())
            ->find($id);
    }

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (!$this->findScopedBooking((int) $id)) {
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

        if (!$this->findScopedBooking((int) $this->diagnostic_test_booking_id)) {
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
