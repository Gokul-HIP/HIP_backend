<?php

namespace App\Livewire\Admin\SecondOpinion;

use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class EditNote extends Component
{
    public $editingNoteId = null;

    public $editingNoteText = '';

    #[On('openEditSecondOpinionNoteModal')]
    public function openEditNoteModal($noteId)
    {
        $note = SecondOpinionStatus::find($noteId);

        if ($note && $note->notes_by == Auth::id()) {
            $this->editingNoteId = $noteId;
            $this->editingNoteText = $note->notes;
            Flux::modal('edit-second-opinion-note')->show();
        } else {
            $this->dispatch('toast', type: 'error', message: 'You can only edit your own notes!');
        }
    }

    public function updateNote()
    {
        $this->validate([
            'editingNoteText' => 'required|string|max:1000',
        ]);

        $note = SecondOpinionStatus::find($this->editingNoteId);

        if ($note && $note->notes_by == Auth::id()) {
            $note->update([
                'notes' => $this->editingNoteText,
            ]);

            $this->dispatch('refreshSecondOpinionDetails');
            $this->closeEditNoteModal();

            $this->dispatch('toast', type: 'success', message: 'Note updated successfully!');
        }
    }

    public function closeEditNoteModal()
    {
        $this->editingNoteId = null;
        $this->editingNoteText = '';
        Flux::modal('edit-second-opinion-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.second-opinion.edit-note');
    }
}
