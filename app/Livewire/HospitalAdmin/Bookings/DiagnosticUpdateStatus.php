<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Models\Hospital;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DiagnosticUpdateStatus extends Component
{
    public $id;
    public $status;
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
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
    }

    public function closeModal(): void
    {
        $this->reset(['status', 'id', 'note']);
        Flux::modal('update-status')->close();
    }

    public function render()
    {
        return view('livewire.admin.diagnostic-test-booking.update-status');
    }
}
