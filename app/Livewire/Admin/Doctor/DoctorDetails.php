<?php

namespace App\Livewire\Admin\Doctor;

use Flux\Flux;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\DoctorProfileService;

class DoctorDetails extends Component
{

    public $doctor;
    public $doctor_id;
    public $tab = 'profile';
    public $linkedHospitals = [];
    public $hospitalId;

    protected $doctorProfileService;

    public function boot(DoctorProfileService $doctorProfileService)
    {
        $this->doctorProfileService = $doctorProfileService;
    }

    #[On('view-doc')]
    public function viewDoctor($id){
        
        $this->doctor_id = $id;
        $this->doctor = $this->doctorProfileService->findDoctor($id);

        $this->refreshLinkedHospitals();

        $this->tab = 'profile';
        Flux::modal('doctor-details')->show();
        $this->dispatch('relod-doc');

    }

    public function unlink($hospitalId){

        if (!$this->doctor) {
            return;
        }

        $this->doctorProfileService->unlinkHospital($this->doctor_id, $hospitalId);
        $this->doctor = $this->doctorProfileService->findDoctor($this->doctor_id);

        $this->dispatch('relod-doc');

        $this->refreshLinkedHospitals();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Hospital unlinked successfully!'
        );

    }

    protected function refreshLinkedHospitals()
    {
        $this->linkedHospitals = $this->doctorProfileService->getLinkedHospitals($this->doctor_id);
    }

    public function render()
    {
        return view('livewire.admin.doctor.doctor-details');
    }
}
