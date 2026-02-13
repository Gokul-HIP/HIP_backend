<?php

namespace App\Livewire\Admin\Organization\Diagnostic;

use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Organization;
use App\Services\DiagnosticService;

class AddDiagnostic extends Component
{
    use WithFileUploads;

    public Organization $organization;

    #[Rule('required')]
    public $diagnostic_center_name;

    #[Rule('required')]
    public $diagnostic_center_address;

    #[Rule('required|image|max:2048')]
    public $diagnostic_logo;

    #[Rule('required')]
    public $diagnostic_contact_person_name;

    #[Rule('required|digits:10|unique:diagnostics,contact_person_number')]
    public $diagnostic_contact_person_number;

    #[Rule('required|email|unique:diagnostics,contact_person_email')]
    public $diagnostic_contact_person_email;

    #[Rule('required')]
    public $diagnostic_contcat_person_address;

    #[Rule('required|numeric|between:-180,180')]
    public $diagnostic_contact_person_longitude;

    #[Rule('required|numeric|between:-90,90')]
    public $diagnostic_contact_person_latitude;
    public $status = false;
    public $org_id;

    protected $diagnosticService;

    public function boot(DiagnosticService $diagnosticService)
    {
        $this->diagnosticService = $diagnosticService;
    }

    public function mount($orgId){
        
        $this->org_id = $orgId;
        $this->organization = Organization::find($orgId);

    }

    #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function removeImage()
    {
        $this->diagnostic_logo = null;
    }

    public function resetInput()
    {
        $this->reset(['diagnostic_center_name','diagnostic_center_address','diagnostic_logo','diagnostic_contact_person_name','diagnostic_contact_person_number'
            ,'diagnostic_contact_person_email','diagnostic_contcat_person_address','diagnostic_contact_person_longitude','diagnostic_contact_person_latitude','status']);
        $this->status = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->removeImage();
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-diagnostic')->close();
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

    public function addDiagnosticCenter(){

        $this->validate();

        $data = [
            'name'              => $this->diagnostic_center_name,
            'address'           => $this->diagnostic_center_address,
            'contact_person_name'      => $this->diagnostic_contact_person_name,
            'contact_person_number'   => $this->diagnostic_contact_person_number,
            'contact_person_email'    => $this->diagnostic_contact_person_email,
            'contact_person_address'  => $this->diagnostic_contcat_person_address,
            'contact_person_longitude' => $this->diagnostic_contact_person_longitude,
            'contact_person_latitude'  => $this->diagnostic_contact_person_latitude,
            'status'                              => $this->status,
        ];

        $diagnosticName = $this->diagnostic_center_name;
        $this->diagnosticService->createDiagnostic($this->org_id, $data, $this->diagnostic_logo);

        $this->closeModal();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Diagnostic Center '.$diagnosticName.' added successfully!'
        );
        $this->dispatch('relodDia');

    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.add-diagnostic');
    }
}
