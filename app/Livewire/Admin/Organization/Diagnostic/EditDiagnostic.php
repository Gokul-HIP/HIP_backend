<?php

namespace App\Livewire\Admin\Organization\Diagnostic;

use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Services\DiagnosticService;

class EditDiagnostic extends Component
{   
     use WithFileUploads;

    #[Rule('required')]
    public $diagnostic_center_name;

    #[Rule('required')]
    public $diagnostic_center_address;

    public $diagnostic_logo;

    #[Rule('required')]
    public $diagnostic_contact_person_name;

    #[Rule('required')]
    public $diagnostic_contact_person_number;

    #[Rule('required')]
    public $diagnostic_contact_person_email;

    #[Rule('required')]
    public $diagnostic_contcat_person_address;

    #[Rule('required|numeric|between:-180,180')]
    public $diagnostic_contact_person_longitude;

    #[Rule('required|numeric|between:-90,90 ')]
    public $diagnostic_contact_person_latitude;
    public $status = false;
    public $diagnostic_id;
    public $old_diagnostic_logo;
    public $remove_image = false;

    protected $diagnosticService;

    public function boot(DiagnosticService $diagnosticService)
    {
        $this->diagnosticService = $diagnosticService;
    }

     #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function removeImage()
    {
        $this->diagnostic_logo = null;
        $this->remove_image = true;
    }

    public function restoreImage()
    {
        $this->diagnostic_logo = null;
        $this->remove_image = false;
    }

    public function resetInput()
    {
        $this->reset(['diagnostic_center_name','diagnostic_center_address','diagnostic_logo','diagnostic_contact_person_name','diagnostic_contact_person_number'
            ,'diagnostic_contact_person_email','diagnostic_contcat_person_address','diagnostic_contact_person_longitude','diagnostic_contact_person_latitude','status','old_diagnostic_logo','remove_image']);
        $this->status = false;
        $this->remove_image = false;
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-diagnostic')->close();
    }

    public function messages(){

        return [
            'diagnostic_center_name.required'              => 'Diagnostic Center Name field is required.',
            'diagnostic_center_address.required'           => 'Diagnostic Center Address field is required.',
            'diagnostic_logo.required'                     => 'Diagnostic Logo field is required.',
            'diagnostic_logo.image'                        => 'Only image files are allowed.',
            'diagnostic_logo.max'                          => 'The logo may not be greater than 2MB.',
            'diagnostic_contact_person_name.required'      => 'Contact Person Name field is required.',
            'diagnostic_contact_person_number.required'    => 'Contact Person Number field is requird.',
            'diagnostic_contact_person_email.required'     => 'Contact Person Email field is required.',
            'diagnostic_contact_person_email.email'        => 'Only Valid Email allowed.',
            'diagnostic_contcat_person_address.required'   => 'Contact Person Address field is required.',
            'diagnostic_contact_person_longitude.required' => 'Longitude field is required.',
            'diagnostic_contact_person_latitude.required'  => 'Latitude field is required.',
            'diagnostic_contact_person_number.digits'      => 'Contact Person Number must be 10 digits.',
            'diagnostic_contact_person_number.unique'      => 'Contact Person Number already exists.',
            'diagnostic_contact_person_email.unique'       => 'Contact Person Email already exists.'
        ];

    }

    #[On('edit')]
    public function editDiagnostic($id){
        
        $data = $this->diagnosticService->findDiagnostic($id);

        $this->diagnostic_id = $id;

        $this->diagnostic_center_name              = $data->diagnostic_center_name;
        $this->diagnostic_center_address           = $data->diagnostic_center_address;
        $this->diagnostic_contact_person_name      = $data->diagnostic_contact_person_name;
        $this->diagnostic_contact_person_number    = $data->diagnostic_contact_person_number;
        $this->diagnostic_contact_person_email     = $data->diagnostic_contact_person_email;
        $this->diagnostic_contcat_person_address   = $data->diagnostic_contcat_person_address;
        $this->diagnostic_contact_person_longitude = $data->diagnostic_contact_person_longitude;
        $this->diagnostic_contact_person_latitude  = $data->diagnostic_contact_person_latitude;
        $this->old_diagnostic_logo                 = $data->diagnostic_logo;
        $this->status                              = $data->status === 'active';
        $this->diagnostic_logo = null;
        $this->dispatch('relodDia');
        Flux::modal('edit-diagnostic')->show();

    }

    public function updateDiagnosticCenter(){

        $this->validate([
            'diagnostic_center_name'              => 'required',
            'diagnostic_center_address'           => 'required',
            'diagnostic_contact_person_name'      => 'required',
            'diagnostic_contact_person_number'    => 'required|digits:10|unique:diagnostics,diagnostic_contact_person_number,' . $this->diagnostic_id,
            'diagnostic_contact_person_email'     => 'required|email|unique:diagnostics,diagnostic_contact_person_email,' . $this->diagnostic_id,
            'diagnostic_contcat_person_address'   => 'required',
            'diagnostic_contact_person_longitude' => 'required|numeric|between:-180,180',
            'diagnostic_contact_person_latitude'  => 'required|numeric|between:-90,90',
        ]);

        $data = [
            'diagnostic_center_name'              => $this->diagnostic_center_name,
            'diagnostic_center_address'           => $this->diagnostic_center_address,
            'diagnostic_contact_person_name'      => $this->diagnostic_contact_person_name,
            'diagnostic_contact_person_number'   => $this->diagnostic_contact_person_number,
            'diagnostic_contact_person_email'    => $this->diagnostic_contact_person_email,
            'diagnostic_contcat_person_address'  => $this->diagnostic_contcat_person_address,
            'diagnostic_contact_person_longitude' => $this->diagnostic_contact_person_longitude,
            'diagnostic_contact_person_latitude'  => $this->diagnostic_contact_person_latitude,
            'status'                              => $this->status,
        ];

        $diagnosticName = $this->diagnostic_center_name;
        $this->diagnosticService->updateDiagnostic(
            $this->diagnostic_id,
            $data,
            $this->diagnostic_logo,
            $this->remove_image
        );

        $this->resetInput();
        Flux::modal('edit-diagnostic')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Diagnostic Center '.$diagnosticName.' updated successfully!'
        );
        $this->dispatch('relodDia');

    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.edit-diagnostic');
    }
}
