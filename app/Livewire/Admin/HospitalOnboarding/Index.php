<?php

namespace App\Livewire\Admin\HospitalOnboarding;

use Livewire\Component;
use App\Models\Hospital;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public $onboarding_status = 'all';
    public $search = '';

    public function render()
    {
        $query = Hospital::where('onboarding_status', '!=', 'draft');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('admin_name', 'like', '%' . $this->search . '%')
                  ->orWhere('admin_email', 'like', '%' . $this->search . '%')
                  ->orWhere('onboarding_status', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.admin.hospital-onboarding.index', [
            'hospitals' => $query->orderBy('created_at', 'desc')->paginate(10),
            'totalHospitals' => Hospital::where('onboarding_status','!=','draft')->count(),
            'activeHospitals' => Hospital::where('onboarding_status','approved')->count(),
        ]);
    }
}
