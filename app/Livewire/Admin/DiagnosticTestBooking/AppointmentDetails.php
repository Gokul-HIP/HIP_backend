<?php

namespace App\Livewire\Admin\DiagnosticTestBooking;

use Livewire\Component;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;
use App\Services\NotificationService;

class AppointmentDetails extends Component
{
    public $id;
    public $diagnosticBooking;
    public $statuses;
    public $statusHistory;
    public $notesHistory;
    public $notes;
    public $deleteNoteId;

    public function mount($id)
    {
        $this->id = $id;
        $this->loadData();
    }

    public function loadData()
    {
        $this->diagnosticBooking = DiagnosticTestBooking::with('member', 'diagnosticCenter', 'statuses.changedBy', 'statuses.notesBy')
            ->find($this->id);

        $this->statuses = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();

        $this->statusHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->latest()
            ->get();

        $this->notesHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->diagnosticBooking->notes()->latest()->first();
    }

    public function openAddNoteModal()
    {
        $this->dispatch('openAddNoteModal', id: $this->id);
    }

    public function openEditNoteModal($noteId)
    {
        $this->dispatch('openEditNoteModal', noteId: $noteId);
    }

    public function openDeleteNoteModal($id)
    {
        $note = DiagnosticTestBookingStatus::find($id);

        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-note')->show();
            $this->deleteNoteId = $id;
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    public function closeDeleteNoteModal()
    {
        $this->deleteNoteId = null;
        Flux::modal('delete-note')->close();
    }

    public function deleteNote()
    {
        $note = DiagnosticTestBookingStatus::find($this->deleteNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->delete();
            $this->loadData();
            $this->closeDeleteNoteModal();
            $this->dispatch('toast', type: 'success', message: 'Note deleted successfully!');
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    #[On('refreshAppointmentDetails')]
    public function refresh()
    {
        $this->loadData();
    }

    public function updateStatusInstant($status)
    {
        $booking = DiagnosticTestBooking::find($this->id);

        $oldStatus = $booking->status;

        $booking->status = $status;
        $booking->save();

        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status'   => $status,
            'changed_by'  => Auth::user()->id,
        ]);

        // Send notification based on the new status
        // if ($status === 'completed') {
        //     $this->sendReviewNotification($booking);
        // }

        if ($status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->loadData();

        $this->dispatch('toast', type: 'success', message: 'Status updated for ' . $booking->name . ' successfully!');
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

    public function render()
    {
        return view('livewire.admin.diagnostic-test-booking.appointment-details');
    }
}