<?php

namespace App\Livewire\Admin\ProcedureBooking;

use Livewire\Component;
use App\Models\ProcedureBookingStatus;
use Illuminate\Support\Facades\Auth;
use Flux\Flux;
use Livewire\Attributes\On;

class EditNote extends Component
{
    public $noteId;
    public $noteText;
    public $notes;
    public $editingNoteId = null;
    public $editingNoteText = '';

    #[On('openEditNoteModal')]
    public function openEditNoteModal($noteId)
    {
        $note = ProcedureBookingStatus::find($noteId);
        
        if ($note && $note->notes_by == Auth::id()) {
            $this->editingNoteId = $noteId;
            $this->editingNoteText = $note->notes;
            Flux::modal('edit-note')->show();
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only edit your own notes!');
        }
    }

    public function updateNote()
    {
        $this->validate([
            'editingNoteText' => 'required|string|max:1000'
        ]);

        $note = ProcedureBookingStatus::find($this->editingNoteId);
        
        if ($note && $note->notes_by == Auth::id()) {
            $note->update([
                'notes' => $this->editingNoteText,
            ]);
            
            $this->dispatch('refreshAppointmentDetails');
            $this->closeEditNoteModal();
        }
    }
    
    public function closeEditNoteModal()
    {
        $this->editingNoteId = null;
        $this->editingNoteText = '';
        Flux::modal('edit-note')->close();
    }
    
    public function render()
    {
        return view('livewire.admin.procedure-booking.edit-note');
    }
}

