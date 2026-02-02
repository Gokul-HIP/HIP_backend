<?php

namespace App\Livewire\Admin\DoctorBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\DoctorBooking;
use Livewire\Attributes\On;
use App\Models\DoctorBookingStatus;
use Illuminate\Support\Facades\Auth;

class UpdateStatus extends Component
{
    public $id;
    public $status;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = DoctorBooking::find($this->id)->status;
        Flux::modal('update-status')->show();
    }

    public function updateStatus()
    {
        $doctorBooking = DoctorBooking::find($this->id);

        $oldStatus = $doctorBooking->status;

        $doctorBooking->status = $this->status;
        $doctorBooking->save();

        DoctorBookingStatus::create([
            'doctor_booking_id' => $doctorBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $this->status,
            'changed_by' => Auth::user()->id,
        ]);
        
        $this->dispatch('refreshDoctorBookings');
        $this->closeModal();
        
        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$doctorBooking->name.' successfully!');
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
        return view('livewire.admin.doctor-booking.update-status');
    }
}
