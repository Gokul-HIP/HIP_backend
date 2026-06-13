<?php

namespace App\Livewire\Admin\SecondOpinion;

use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class AddNote extends Component
{
    public $second_opinion_id;

    public $note;

    #[On('openAddSecondOpinionNoteModal')]
    public function openAddNoteModal($id)
    {
        $this->second_opinion_id = $id;
        $this->note = '';
        Flux::modal('add-second-opinion-note')->show();
    }

    public function addNote()
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        SecondOpinionStatus::create([
            'second_opinion_id' => $this->second_opinion_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);

        $this->closeModal();

        $this->dispatch('toast', type: 'success', message: 'Note added successfully!');
        $this->dispatch('refreshSecondOpinionDetails');
    }

    public function closeModal()
    {
        $this->reset(['note', 'second_opinion_id']);
        Flux::modal('add-second-opinion-note')->close();
    }

    public function render()
    {
        return view('livewire.admin.second-opinion.add-note');
    }
}
