<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DoctorUpdateStatus extends Component
{
    public $id;
    public $status;
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

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id): void
    {
        $booking = $this->findScopedBooking((int) $id);

        if (!$booking) {
            return;
        }

        $this->id = $booking->id;
        $this->status = $booking->status;
        $this->note = '';
        Flux::modal('update-status')->show();
    }

    public function updateStatus(): void
    {
        $booking = $this->findScopedBooking((int) $this->id);

        if (!$booking) {
            $this->closeModal();
            return;
        }

        $oldStatus = $booking->status;
        $booking->status = $this->status;
        $booking->save();

        if ($this->status !== $oldStatus) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $booking->id,
                'from_status' => $oldStatus,
                'to_status' => $this->status,
                'changed_by' => Auth::id(),
            ]);
        }

        if ($this->note) {
            DoctorBookingStatus::create([
                'doctor_booking_id' => $booking->id,
                'notes' => $this->note,
                'notes_by' => Auth::id(),
            ]);
        }

        $this->dispatch('refreshDoctorBookings');
        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
    }

    public function closeModal(): void
    {
        $this->reset(['status', 'id', 'note']);
        Flux::modal('update-status')->close();
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.update-status');
    }
}
