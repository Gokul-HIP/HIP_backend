<?php

namespace App\Livewire\TechnicianAdmin\DiagnosticTestBooking;

use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Services\NotificationService;
use App\Services\TechnicianDiagnosticScopeService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class UpdateStatus extends Component
{
    public $id;

    public $status;

    public $note;

    #[On('openUpdateStatusModal')]
    public function openUpdateStatusModal($id): void
    {
        $booking = app(TechnicianDiagnosticScopeService::class)->findScopedBooking((int) $id);

        if (! $booking) {
            return;
        }

        $this->id = $id;
        $this->status = $booking->status;
        $this->note = '';
        Flux::modal('update-status')->show();
    }

    public function updateStatus(): void
    {
        $booking = app(TechnicianDiagnosticScopeService::class)->findScopedBooking((int) $this->id);

        if (! $booking) {
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

        if ($this->status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($this->status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->dispatch('refreshDiagnosticTestBookings');
        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
    }

    protected function sendConfirmationNotification(DiagnosticTestBooking $booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        $notificationService = app(NotificationService::class);
        $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';
        $bookingDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $notificationService->notifyUser((string) $booking->member_id, 'Your Package booking is confirmed!', 'Your ' . $packageLabel . ' booking has been confirmed for ' . $bookingDate . '.', [
            'type' => 'package_confirmed',
            'screen' => 'booking_history',
            'entity_type' => 'diagnostic_center',
            'entity_id' => (string) ($booking->diagnostic_center_id ?? ''),
            'booking_type' => 'diagnostic_package',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'package_type' => (string) ($booking->package_type ?? ''),
            'booking_date' => $booking->booking_date?->format('Y-m-d') ?? '',
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
    }

    protected function sendCancellationNotification(DiagnosticTestBooking $booking): void
    {
        if (! $booking->member_id) {
            return;
        }

        $notificationService = app(NotificationService::class);
        $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';
        $bookingDate = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $notificationService->notifyUser((string) $booking->member_id, 'Your Package booking is cancelled!', 'Your ' . $packageLabel . ' booking for ' . $bookingDate . ' has been cancelled.', [
            'type' => 'package_cancelled',
            'screen' => 'booking_history',
            'entity_type' => 'diagnostic_center',
            'entity_id' => (string) ($booking->diagnostic_center_id ?? ''),
            'booking_type' => 'diagnostic_package',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'package_type' => (string) ($booking->package_type ?? ''),
            'booking_date' => $booking->booking_date?->format('Y-m-d') ?? '',
            'url' => '/booking-history',
            'route' => '/booking-history',
        ]);
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
