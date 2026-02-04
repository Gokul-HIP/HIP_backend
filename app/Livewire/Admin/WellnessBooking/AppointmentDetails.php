<?php

namespace App\Livewire\Admin\WellnessBooking;

use Livewire\Component;
use App\Models\WellnessBooking;
use App\Models\WellnessBookingStatus;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

class AppointmentDetails extends Component
{
    public $id;
    public $wellnessBooking;
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
        $this->wellnessBooking = WellnessBooking::with('member', 'center', 'statuses.changedBy', 'statuses.notesBy')
            ->find($this->id);
        
        $this->statuses = $this->wellnessBooking->statuses()
            ->whereNotNull('from_status')
            ->whereNotNull('to_status')
            ->with('changedBy')
            ->latest()
            ->first();
        
        $this->statusHistory = $this->wellnessBooking->statuses()->whereNotNull('from_status')->whereNotNull('to_status')
            ->orderBy('created_at', 'desc')->limit(5)->latest()->get();
        
        $this->notesHistory = $this->wellnessBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->wellnessBooking->notes()->latest()->first();
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
        $note = WellnessBookingStatus::find($id);
        
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
        $note = WellnessBookingStatus::find($this->deleteNoteId);
        
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
        $wellnessBooking = WellnessBooking::find($this->id);

        $oldStatus = $wellnessBooking->status;

        $wellnessBooking->status = $status;
        $wellnessBooking->save();

        WellnessBookingStatus::create([
            'wellness_booking_id' => $wellnessBooking->id,
            'from_status' => $oldStatus,
            'to_status' => $status,
            'changed_by' => Auth::user()->id,
        ]);
        
        $this->loadData();
        
        $this->dispatch('toast', type: 'success', message: 'Status updated for '.$wellnessBooking->name.' successfully!');
    }

    public function render()
    {
        return view('livewire.admin.wellness-booking.appointment-details');
    }
}

