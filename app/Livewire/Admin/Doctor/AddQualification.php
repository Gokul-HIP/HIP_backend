<?php

namespace App\Livewire\Admin\Doctor;

use Livewire\Component;
use Flux\Flux;
use App\Models\MasterQualification;

class AddQualification extends Component
{
    public $new_qualification;
    public $new_qualification_description;

    public function addQualification()
    {
        $this->validate([
            'new_qualification' => 'required|string|max:255',
            'new_qualification_description' => 'nullable|string',
        ]);
    
        $qualification = MasterQualification::create([
            'name' => $this->new_qualification,
            'description' => $this->new_qualification_description,
        ]);
    
        $this->resetForm();
    
        $this->dispatch('qualification-added', id: $qualification->id);
        Flux::modal('add-qualification')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'New '.$qualification->name.' qualification added successfully!'
        );

    }

    public function resetForm()
    {
        $this->reset([
            'new_qualification',
            'new_qualification_description',
        ]);

        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.doctor.add-qualification');
    }
}