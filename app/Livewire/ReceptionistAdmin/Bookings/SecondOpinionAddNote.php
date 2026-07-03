<?php

namespace App\Livewire\ReceptionistAdmin\Bookings;

use App\Livewire\ReceptionistAdmin\Concerns\ScopesReceptionistBookings;
use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SecondOpinionAddNote extends Component
{
    use ScopesReceptionistBookings;

    public $second_opinion_id;
    public $note;

    #[On('openHealthcareSecondOpinionNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (! $this->scopeService()->findSecondOpinion((int) $id)) {
            return;
        }

        $this->second_opinion_id = (int) $id;
        $this->note = '';
        Flux::modal('add-second-opinion-note')->show();
    }

    public function addNote(): void
    {
        $this->validate([
            'note' => 'required|string|max:1000',
        ]);

        if (! $this->scopeService()->findSecondOpinion((int) $this->second_opinion_id)) {
            $this->closeModal();

            return;
        }

        SecondOpinionStatus::create([
            'second_opinion_id' => $this->second_opinion_id,
            'notes' => $this->note,
            'notes_by' => Auth::id(),
        ]);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: 'Note added successfully!');
        $this->dispatch('refreshAppointmentDetails');
    }

    public function closeModal(): void
    {
        $this->reset(['note', 'second_opinion_id']);
        Flux::modal('add-second-opinion-note')->close();
    }

    public function render()
    {
        return view('livewire.hospital-admin.bookings.second-opinion-add-note');
    }
}
