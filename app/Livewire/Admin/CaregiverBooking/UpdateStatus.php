<?php

namespace App\Livewire\Admin\CaregiverBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\CaregiverBooking;
use Livewire\Attributes\On;
use App\Models\CaregiverBookingStatus;
use Illuminate\Support\Facades\Auth;

class UpdateStatus extends Component
{
    public $id;
    public $status;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = CaregiverBooking::find($this->id)->status;
        Flux::modal('update-status')->show();
    }

    public function updateStatus()
    {
        $caregiverBooking = CaregiverBooking::find($this->id);

        $oldStatus = $caregiverBooking->status;

        $caregiverBooking->status = $this->status;
        $caregiverBooking->save();

        CaregiverBookingStatus::create([
            'caregiver_booking_id' => $caregiverBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $this->status,
            'changed_by' => Auth::user()->id,
        ]);

        $this->dispatch('refreshCaregiverBookings');
        $this->closeModal();

        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$caregiverBooking->name.' successfully!');
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
        return view('livewire.admin.caregiver-booking.update-status');
    }
}
