<?php

namespace App\Livewire\Admin\ProcedureBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\ProcedureBooking;
use Livewire\Attributes\On;
use App\Models\ProcedureBookingStatus;
use Illuminate\Support\Facades\Auth;

class UpdateStatus extends Component
{
    public $id;
    public $status;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = ProcedureBooking::find($this->id)->status;
        Flux::modal('update-status')->show();
    }

    public function updateStatus()
    {
        $this->validate([
            'status' => 'required|in:pending,confirmed,cancelled,completed',
        ]);

        $procedureBooking = ProcedureBooking::find($this->id);

        $oldStatus = $procedureBooking->status;

        $procedureBooking->status = $this->status;
        $procedureBooking->save();

        ProcedureBookingStatus::create([
            'procedure_booking_id' => $procedureBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $this->status,
            'changed_by' => Auth::user()->id,
        ]);
        
        $this->dispatch('refreshProcedureBookings');
        $this->closeModal();
        
        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$procedureBooking->name.' successfully!');
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
        return view('livewire.admin.procedure-booking.update-status');
    }
}

