<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Hospital;
use App\Models\ProcedureBooking;
use App\Models\ProcedureBookingStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ProcedureAddNote extends Component
{
    public $procedure_booking_id;
    public $note;

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function findScopedBooking(?int $id): ?ProcedureBooking
    {
        if (!$id) {
            return null;
        }

        return ProcedureBooking::query()
            ->whereIn('hospital_id', $this->organizationHospitalIds())
            ->find($id);
    }

    #[On('openAddNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (!$this->findScopedBooking((int) $id)) {
            return;
        }

        $this->procedure_booking_id = (int) $id;
        $this->note = '';
        Flux::modal('add-note')->show();
    }

    public function addNote(): void
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        if (!$this->findScopedBooking((int) $this->procedure_booking_id)) {
            $this->closeModal();
            return;
        }

        ProcedureBookingStatus::create([
            'procedure_booking_id' => $this->procedure_booking_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);

        $this->dispatch('refreshAppointmentDetails');
        $this->closeModal();
    }

    public function closeModal(): void
    {
        $this->reset(['note', 'procedure_booking_id']);
        Flux::modal('add-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.procedure-booking.add-note');
    }
}
