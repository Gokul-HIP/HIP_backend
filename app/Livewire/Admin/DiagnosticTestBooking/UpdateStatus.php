<?php

namespace App\Livewire\Admin\DiagnosticTestBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\DiagnosticTestBooking;
use Livewire\Attributes\On;
use App\Models\DiagnosticTestBookingStatus;
use Illuminate\Support\Facades\Auth;

class UpdateStatus extends Component
{
    public $id;
    public $status;
    public $note;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id)
    {
        $this->id = $id;
        $this->status = DiagnosticTestBooking::find($this->id)->status;
        $this->note = '';
        Flux::modal('update-status')->show();
    }

    public function updateStatus()
    {
        $booking = DiagnosticTestBooking::find($this->id);

        $oldStatus = $booking->status;

        $booking->status = $this->status;
        $booking->save();

        if ($this->status !== $oldStatus) {
        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status' => $this->status,
            'changed_by' => Auth::id(),
        ]);
        }

        if ($this->note) {
            DiagnosticTestBookingStatus::create([
                'diagnostic_test_booking_id' => $booking->id,
                'notes' => $this->note,
                'notes_by' => Auth::id(),
            ]);
        }

        $this->dispatch('refreshDiagnosticTestBookings');
        $this->closeModal();

        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$booking->name.' successfully!');
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
        return view('livewire.admin.diagnostic-test-booking.update-status');
    }
}
