<?php

namespace App\Livewire\Admin\DiagnosticTestBooking;

use Livewire\Component;
use Flux\Flux;
use App\Models\DiagnosticTestBooking;
use Livewire\Attributes\On;
use App\Models\DiagnosticTestBookingStatus;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

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

        // if ($this->status === 'completed') {
        //     $this->sendReviewNotification($booking);
        // }

        if ($this->status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($this->status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->dispatch('refreshDiagnosticTestBookings');
        $this->closeModal();

        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$booking->name.' successfully!');
    }

    // protected function sendReviewNotification(DiagnosticTestBooking $booking): void
    // {
    //     if (!$booking->member_id) {
    //         return;
    //     }

    //     $notificationService = app(NotificationService::class);

    //     $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';

    //     $title = 'How was your experience?';
    //     $body  = 'Please review the diagnostic center for your recent ' . $packageLabel . ' booking.';

    //     $data = [
    //         'type'                       => 'review_popup',
    //         'entity_type'                => 'diagnostic_center',
    //         'entity_id'                  => (string) ($booking->diagnostic_center_id ?? ''),
    //         'booking_type'               => 'diagnostic_package',
    //         'diagnostic_test_booking_id' => (string) $booking->id,
    //         'screen'                     => 'booking_history',
    //         'url'                        => '/booking-history',
    //         'route'                      => '/booking-history',
    //     ];

    //     $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    // }

    protected function sendConfirmationNotification(DiagnosticTestBooking $booking): void
    {
        if (!$booking->member_id) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';
        $bookingDate  = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $title = 'Your Package booking is confirmed!';
        $body  = 'Your ' . $packageLabel . ' booking has been confirmed for ' . $bookingDate . '.';

        $data = [
            'type'                       => 'package_confirmed',
            'screen'                     => 'booking_history',
            'entity_type'                => 'diagnostic_center',
            'entity_id'                  => (string) ($booking->diagnostic_center_id ?? ''),
            'booking_type'               => 'diagnostic_package',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'package_type'               => (string) ($booking->package_type ?? ''),
            'booking_date'               => $booking->booking_date?->format('Y-m-d') ?? '',
            'url'                        => '/booking-history',
            'route'                      => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
    }

    protected function sendCancellationNotification(DiagnosticTestBooking $booking): void
    {
        if (!$booking->member_id) {
            return;
        }

        $notificationService = app(NotificationService::class);

        $packageLabel = $booking->package_type === 'disease' ? 'Disease Package' : 'Diagnostic Package';
        $bookingDate  = $booking->booking_date
            ? $booking->booking_date->format('d M Y')
            : 'your scheduled date';

        $title = 'Your Package booking is cancelled!';
        $body  = 'Your ' . $packageLabel . ' booking for ' . $bookingDate . ' has been cancelled.';

        $data = [
            'type'                       => 'package_cancelled',
            'screen'                     => 'booking_history',
            'entity_type'                => 'diagnostic_center',
            'entity_id'                  => (string) ($booking->diagnostic_center_id ?? ''),
            'booking_type'               => 'diagnostic_package',
            'diagnostic_test_booking_id' => (string) $booking->id,
            'package_type'               => (string) ($booking->package_type ?? ''),
            'booking_date'               => $booking->booking_date?->format('Y-m-d') ?? '',
            'url'                        => '/booking-history',
            'route'                      => '/booking-history',
        ];

        $notificationService->notifyUser((string) $booking->member_id, $title, $body, $data);
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
