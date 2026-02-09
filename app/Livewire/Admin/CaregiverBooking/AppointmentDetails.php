<?php

namespace App\Livewire\Admin\CaregiverBooking;

use Livewire\Component;
use App\Models\CaregiverBooking;
use App\Models\CaregiverBookingStatus;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

class AppointmentDetails extends Component
{
    public $id;
    public $caregiverBooking;
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
        $this->caregiverBooking = CaregiverBooking::with('member', 'caregiver', 'wellnessCenter', 'statuses.changedBy', 'statuses.notesBy')
            ->find($this->id);

        $this->statuses = $this->caregiverBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();

        $this->statusHistory = $this->caregiverBooking->statuses()->whereNotNull('from_status')->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')->limit(5)->latest()->get();

        $this->notesHistory = $this->caregiverBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->caregiverBooking->notes()->latest()->first();
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
        $note = CaregiverBookingStatus::find($id);

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
        $note = CaregiverBookingStatus::find($this->deleteNoteId);

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
        $caregiverBooking = CaregiverBooking::find($this->id);

        $oldStatus = $caregiverBooking->status;

        $caregiverBooking->status = $status;
        $caregiverBooking->save();

        CaregiverBookingStatus::create([
            'caregiver_booking_id' => $caregiverBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::user()->id,
        ]);

        $this->loadData();

        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$caregiverBooking->name.' successfully!');
    }

    public function render()
    {
        return view('livewire.admin.caregiver-booking.appointment-details');
    }
}
