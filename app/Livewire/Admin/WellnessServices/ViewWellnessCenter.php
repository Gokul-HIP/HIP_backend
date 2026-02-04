<?php

namespace App\Livewire\Admin\WellnessServices;

use Livewire\Component;
use App\Models\WellnessCenters;

class ViewWellnessCenter extends Component
{
    public $id;
    public $wellnessCenter;

    public function mount($id)
    {
        $this->id = $id;
        $this->loadWellnessCenter();
    }

    public function loadWellnessCenter()
    {
        $this->wellnessCenter = WellnessCenters::with('wellnessCategory')->findOrFail($this->id);
    }

    public function render()
    {
        return view('livewire.admin.wellness-services.view-wellness-center');
    }
}

