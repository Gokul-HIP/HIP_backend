<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Livewire\Component;
use App\Models\Hospital;

class HospitalDetails extends Component
{
    public $hospitalId;

    public function mount($hospitalId){
        $this->hospitalId = $hospitalId;
    }

    public function render()
    {
        $hospital = Hospital::find($this->hospitalId);
        return view('livewire.admin.organization.hospital.hospital-details', [
            'hospital' => $hospital
        ]);
    }
}
