<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SecondOpinionEditNote extends Component
{
    use ScopesReceptionistBookings;

    public $editingNoteId = null;
    public $editingNoteText = '';

    #[On('openEditHealthcareSecondOpinionNoteModal')]
    public function openEditNoteModal($noteId): void
    {
        $note = SecondOpinionStatus::find($noteId);

        if (
            $note
            && $this->scopeService()->secondOpinionBelongsToScope((int) $note->second_opinion_id)
            && $note->notes_by == Auth::id()
        ) {
            $this->editingNoteId = $noteId;
            $this->editingNoteText = $note->notes;
            Flux::modal('edit-second-opinion-note')->show();

            return;
        }

        $this->dispatch('toast', type: 'error', message: 'You can only edit your own notes!');
    }

    public function updateNote(): void
    {
        $this->validate([
            'editingNoteText' => 'required|string|max:1000',
        ]);

        $note = SecondOpinionStatus::find($this->editingNoteId);

        if (
            $note
            && $this->scopeService()->secondOpinionBelongsToScope((int) $note->second_opinion_id)
            && $note->notes_by == Auth::id()
        ) {
            $note->update(['notes' => $this->editingNoteText]);
            $this->dispatch('refreshAppointmentDetails');
            $this->closeEditNoteModal();
            $this->dispatch('toast', type: 'success', message: 'Note updated successfully!');
        }
    }

    public function closeEditNoteModal(): void
    {
        $this->editingNoteId = null;
        $this->editingNoteText = '';
        Flux::modal('edit-second-opinion-note')->close();
    }

    public function render()
    {
        return view('livewire.hospital-admin.bookings.second-opinion-edit-note');
    }
}
