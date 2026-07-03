<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Services\DoctorBookingStatusService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class DoctorUpdateStatus extends Component
{
    use ScopesReceptionistBookings;

    public $id;
    public $status;
    public $note;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id): void
    {
        $booking = $this->scopeService()->findDoctorBooking((int) $id);

        if (! $booking) {
            return;
        }

        $this->id = $booking->id;
        $this->status = $booking->status;
        $this->note = '';
        Flux::modal('update-status')->show();
    }

    public function updateStatus(DoctorBookingStatusService $statusService): void
    {
        $booking = $this->scopeService()->findDoctorBooking((int) $this->id);

        if (! $booking) {
            $this->closeModal();

            return;
        }

        $statusService->updateBookingStatus(
            $booking,
            $this->status,
            note: $this->note ?: null
        );

        $this->dispatch('refreshReceptionistDoctorBookings');
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
