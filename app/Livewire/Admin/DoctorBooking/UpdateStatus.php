<?php

namespace App\Livewire\Admin\DoctorBooking;

use App\Models\DoctorBooking;
use App\Services\DoctorBookingStatusService;
use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;

class UpdateStatus extends Component
{
    public $id;
    public $status;
    public $note;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = DoctorBooking::find($this->id)->status;
        $this->note = '';
        Flux::modal('update-status')->show();
    }

    public function updateStatus(DoctorBookingStatusService $statusService)
    {
        $doctorBooking = DoctorBooking::find($this->id);

        if (! $doctorBooking) {
            return;
        }

        $statusService->updateBookingStatus(
            $doctorBooking,
            $this->status,
            note: $this->note ?: null
        );

        $this->dispatch('refreshDoctorBookings');
        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$doctorBooking->name.' successfully!');
    }

    public function closeModal()
    {
        $this->status = '';
        $this->id = null;
        $this->reset(['status', 'id']);
        Flux::modal('update-status')->close();
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.update-status');
    }
}
