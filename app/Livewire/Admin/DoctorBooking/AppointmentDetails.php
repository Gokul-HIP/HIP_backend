<?php

namespace App\Livewire\Admin\DoctorBooking;

use Livewire\Component;
use App\Models\DoctorBooking;
use App\Models\DoctorBookingStatus;
use App\Services\DoctorBookingStatusService;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

class AppointmentDetails extends Component
{
    public $id;
    public $doctorBooking;
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
        $this->doctorBooking = DoctorBooking::with('member', 'doctor', 'hospital', 'statuses.changedBy', 'statuses.notesBy')
            ->find($this->id);
        
        $this->statuses = $this->doctorBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->latest()
            ->first();
        
        $this->statusHistory = $this->doctorBooking->statuses()->whereNotNull('from_status')->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')->limit(5)->latest()->get();
        
        $this->notesHistory = $this->doctorBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->doctorBooking->notes()->latest()->first();
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
        $note = DoctorBookingStatus::find($id);
        
        if ($note && $note->notes_by == Auth::id()) {
            Flux::modal('delete-note')->show();
            $this->deleteNoteId = $id;
            // dd($this->deleteNoteId);
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
        $note = DoctorBookingStatus::find($this->deleteNoteId);
        
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

    public function updateStatusInstant($status, DoctorBookingStatusService $statusService)
    {
        $doctorBooking = DoctorBooking::find($this->id);

        if (! $doctorBooking) {
            return;
        }

        $statusService->updateBookingStatus($doctorBooking, $status);

        $this->loadData();

        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$doctorBooking->name.' successfully!');
    }

    public function render()
    {
        return view('livewire.admin.doctor-booking.appointment-details');
    }
}