<?php

namespace App\Livewire\HospitalAdmin\Bookings;

use App\Models\Hospital;
use App\Models\SecondOpinion;
use App\Models\SecondOpinionStatus;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class SecondOpinionAddNote extends Component
{
    public $second_opinion_id;
    public $note;

    protected function organizationHospitalIds(): array
    {
        return Hospital::query()
            ->where('organization_id', Auth::user()->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function findScopedBooking(?int $id): ?SecondOpinion
    {
        if (! $id) {
            return null;
        }

        return SecondOpinion::query()
            ->whereIn('branch_id', $this->organizationHospitalIds())
            ->find($id);
    }

    #[On('openHealthcareSecondOpinionNoteModal')]
    public function openAddNoteModal($id): void
    {
        if (! $this->findScopedBooking((int) $id)) {
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

        if (! $this->findScopedBooking((int) $this->second_opinion_id)) {
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
        $this->dispatch('refreshHealthcareSecondOpinionDetails');
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
