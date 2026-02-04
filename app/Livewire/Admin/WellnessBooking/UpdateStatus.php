<?php

namespace App\Livewire\Admin\WellnessBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\WellnessBooking;
use Livewire\Attributes\On;
use App\Models\WellnessBookingStatus;
use Illuminate\Support\Facades\Auth;

class UpdateStatus extends Component
{
    public $id;
    public $status;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = WellnessBooking::find($this->id)->status;
        Flux::modal('update-status')->show();
    }

    public function updateStatus()
    {
        $this->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $wellnessBooking = WellnessBooking::find($this->id);

        $oldStatus = $wellnessBooking->status;

        $wellnessBooking->status = $this->status;
        $wellnessBooking->save();

        WellnessBookingStatus::create([
            'wellness_booking_id' => $wellnessBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $this->status,
            'changed_by' => Auth::user()->id,
        ]);
        
        $this->dispatch('refreshWellnessBookings');
        $this->closeModal();
        
        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$wellnessBooking->name.' successfully!');
    }

    public function closeModal()
    {
        $this->status = '';
        $this->id = null;
        $this->reset(['status','id']);
        Flux::modal('update-status')->close();
    }

    public function render()
    {
        return view('livewire.admin.wellness-booking.update-status');
    }
}

