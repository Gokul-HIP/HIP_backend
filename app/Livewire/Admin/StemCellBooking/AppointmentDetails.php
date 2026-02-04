<?php

namespace App\Livewire\Admin\StemCellBooking;

use Livewire\Component;
use App\Models\StemCellBooking;
use App\Models\StemCellBookingStatus;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;

class AppointmentDetails extends Component
{
    public $id;
    public $stemCellBooking;
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
        $this->stemCellBooking = StemCellBooking::with('member', 'statuses.notesBy')
            ->find($this->id);
        
        $this->notesHistory = $this->stemCellBooking->statuses()
            ->whereNotNull('notes')
            ->whereNotNull('notes_by')
            ->with('notesBy')
            ->orderBy('created_at', 'desc')
            ->get();

        $this->notes = $this->stemCellBooking->notes()->latest()->first();
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
        $note = StemCellBookingStatus::find($id);
        
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
        $note = StemCellBookingStatus::find($this->deleteNoteId);
        
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

    public function render()
    {
        return view('livewire.admin.stem-cell-booking.appointment-details');
    }
}

