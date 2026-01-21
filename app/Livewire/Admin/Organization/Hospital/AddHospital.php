<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Organization;
use App\Services\HospitalService;
use App\Models\Pharmacy;
use App\Models\Diagnostic;


class AddHospital extends Component
{   
    use WithFileUploads;
    public Organization $organization;

    #[Rule('required')]
    public $hospital_name;

    #[Rule('required')]
    public $hospital_subtitle;

    #[Rule('required')]
    public $hospital_about;

    #[Rule('required')]
    public $hospital_address;

    #[Rule('required|image|max:2048')]
    public $hospital_logo;

    #[Rule('required')]
    public $hospital_admin_name;

    #[Rule('required|digits:10|unique:hospitals,hospital_admin_contact')]
    public $hospital_admin_contact;

    #[Rule('required|email|unique:hospitals,hospital_admin_email')]
    public $hospital_admin_email;

    #[Rule('required')]
    public $hospital_admin_address;

    #[Rule('required|numeric|between:-180,180')]
    public $hospital_admin_longitude;

    #[Rule('required|numeric|between:-90,90')]
    public $hospital_admin_latitude;
    public $org_id;
    public $status = false;
    public array $pharmacy_ids = [];
    public ?int $diagnostic_centers = null;
    public $pharmacies = [];
    public $diagnosticCenters = [];
    public array $selected_pharmacy_ids = [];
    public ?int $selected_diagnostic_id = null;
    protected $hospitalService;

    public function boot(HospitalService $hospitalService)
    {
        $this->hospitalService = $hospitalService;
    }

    public function mount($orgId)
    {

        $this->org_id = $orgId;
        $this->organization = Organization::findOrFail($orgId);

        $this->pharmacies = Pharmacy::where('organization_id', $orgId)->get();
        $this->diagnosticCenters = Diagnostic::where('organization_id', $orgId)->get();

    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.add-hospital');
    }

    #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function removeImage()
    {
        $this->hospital_logo = null;
    }

    public function resetInput()
    {
        $this->reset(['hospital_name', 'hospital_subtitle', 'hospital_about', 'hospital_address', 'hospital_logo', 'hospital_admin_name','hospital_admin_contact','hospital_admin_email',
            'hospital_admin_address' ,'hospital_admin_longitude' ,'hospital_admin_latitude','status', 'selected_pharmacy_ids', 'selected_diagnostic_id']);
        $this->status = false;
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-hospital')->close();
    }

    public function messages()
    {
        return [
            'hospital_name.required'            => 'Hospital Name field is required.',
            'hospital_subtitle.required'        => 'Subtitle field is required.',
            'hospital_about.required'           => 'About field is required.',
            'hospital_address.required'         => 'Address field is required.',
            'hospital_logo.required'            => 'Hospital Logo field is required',
            'hospital_logo.image'               => 'Only image files are allowed.',
            'hospital_logo.max'                 => 'The logo may not be greater than 2MB.',
            'hospital_admin_name.required'      => 'Admin name field is required.',
            'hospital_admin_contact.required'   => 'Contact field is required.',
            'hospital_admin_contact.max'        => 'Contact must be maximum 10 digits.',
            'hospital_admin_contact.min'        => 'Contact must be minimum 10 digits.',
            'hospital_admin_contact.numeric'    => 'Contact must be a number.',
            'hospital_admin_contact.unique'     => 'Contact already exists.',
            'hospital_admin_email.email'        => 'Only valide email allowed.',
            'hospital_admin_email.required'     => 'Email field is required.',
            'hospital_admin_email.unique'       => 'Email already exists.',
            'hospital_admin_address.required'   => 'Address field is required.',
            'hospital_admin_longitude.required' => 'Longitude field is required.',
            'hospital_admin_latitude.required'  => 'Latitude field is required.',
            'pharmacy_ids.required'             => 'Pharmacy field is required.',
            'selected_diagnostic_id.required'     => 'Diagnostic Center field is required.'
            
        ];
    }

    public function addHospital()
    {
        $this->validate();
        $hospitalName = $this->hospital_name;
        $data = [
            'hospital_name'            => $this->hospital_name,
            'hospital_subtitle'        => $this->hospital_subtitle,
            'hospital_about'           => $this->hospital_about,
            'hospital_address'         => $this->hospital_address,
            'hospital_admin_name'      => $this->hospital_admin_name,
            'hospital_admin_contact'   => $this->hospital_admin_contact,
            'hospital_admin_email'     => $this->hospital_admin_email,
            'hospital_admin_address'   => $this->hospital_admin_address,
            'hospital_admin_longitude' => $this->hospital_admin_longitude,
            'hospital_admin_latitude'  => $this->hospital_admin_latitude,
            'status'                   => $this->status,
            'organization_id'          => $this->org_id,
            'pharmacy_ids'             => $this->selected_pharmacy_ids,
            'diagnostic_center_id'     => $this->selected_diagnostic_id
        ];

        $this->hospitalService->createHospital($data, $this->hospital_logo);

        $this->resetInput();
        Flux::modal('add-hospital')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Hospital '.$hospitalName.' added successfully!'
        );
        $this->dispatch('relodHos');
    }


}
