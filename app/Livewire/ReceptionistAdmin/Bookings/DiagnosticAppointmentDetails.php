<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\DiagnosticTestBooking;
use App\Models\DiagnosticTestBookingStatus;
use App\Services\NotificationService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class DiagnosticAppointmentDetails extends Component
{
    use ScopesReceptionistBookings;

    public $id;
    public $diagnosticBooking;
    public $statuses;
    public $statusHistory;
    public $notesHistory;
    public $notes;
    public $deleteNoteId;

    public function mount($id): void
    {
        $this->id = $id;
        $this->loadData();
    }

    protected function scopedBookingQuery()
    {
        $hospitalIds = $this->scopeService()->hospitalIds('all');
        $diagnosticIds = $this->scopeService()->diagnosticCenterIds('all');

        return DiagnosticTestBooking::query()
            ->where(function ($query) use ($hospitalIds, $diagnosticIds) {
                $query->whereIn('branch_id', $hospitalIds);
                if ($diagnosticIds !== []) {
                    $query->orWhereIn('diagnostic_center_id', $diagnosticIds);
                }
            });
    }

    public function loadData(): void
    {
        $this->diagnosticBooking = $this->scopedBookingQuery()
            ->with('member', 'diagnosticCenter', 'branch', 'statuses.changedBy', 'statuses.notesBy')
            ->findOrFail($this->id);

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
            ->get();

        $this->notesHistory = $this->diagnosticBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->diagnosticBooking->notes()->latest()->first();
    }

    public function openAddNoteModal(): void
    {
        $this->dispatch('openAddNoteModal', id: $this->id);
    }

    public function openEditNoteModal($noteId): void
    {
        $this->dispatch('openEditNoteModal', noteId: $noteId);
    }

    public function openDeleteNoteModal($id): void
    {
        $note = DiagnosticTestBookingStatus::find($id);

        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-note')->show();
            $this->deleteNoteId = $id;
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only delete your own notes!');
        }
    }

    public function closeDeleteNoteModal(): void
    {
        $this->deleteNoteId = null;
        Flux::modal('delete-note')->close();
    }

    public function deleteNote(): void
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
    public function refresh(): void
    {
        $this->loadData();
    }

    public function updateStatusInstant($status): void
    {
        $booking = $this->scopedBookingQuery()->find($this->id);

        if (! $booking) {
            return;
        }

        $oldStatus = $booking->status;

        if ($status === $oldStatus) {
            return;
        }

        $booking->status = $status;
        $booking->save();

        DiagnosticTestBookingStatus::create([
            'diagnostic_test_booking_id' => $booking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::id(),
        ]);

        if ($status === 'confirmed') {
            $this->sendConfirmationNotification($booking);
        }

        if ($status === 'cancelled') {
            $this->sendCancellationNotification($booking);
        }

        $this->loadData();
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

    public function render()
    {
        return view('livewire.hospital-admin.bookings.diagnostic-appointment-details', [
            'backRoute' => route('receptionist.diagnostic-bookings.index'),
        ]);
    }
}
