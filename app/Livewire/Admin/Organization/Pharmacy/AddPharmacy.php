<?php

namespace App\Livewire\Admin\Organization\Pharmacy;

use Livewire\Component;
use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Models\Organization;
use App\Services\PharmacyService;

class AddPharmacy extends Component
{
    use WithFileUploads;

    public Organization $organization;

    #[Rule("required")]
    public $pharmacy_name;

    public $pharmacy_id;

    #[Rule("required")]
    public $pharmacy_address;

    #[Rule("required")]
    public $pharmacy_license_number;

    public $pharmacy_gst_num;

    #[Rule("required")]
    public $pharmacy_contact_person_name;

    #[Rule("required|digits:10|unique:pharmacies,pharmacy_contact_person_number")]
    public $pharmacy_contact_person_number;

    #[Rule("required|email|unique:pharmacies,pharmacy_contact_person_email")]
    public $pharmacy_contact_person_email;

    #[Rule("required|image|max:2048")]
    public $pharmacy_logo;

    #[Rule("required")]
    public $pharmacy_opening_time;

    #[Rule("required")]
    public $pharmacy_closing_time;
    
    public $status = false;
    public $org_id;

    protected $pharmacyService;

    public function boot(PharmacyService $pharmacyService)
    {
        $this->pharmacyService = $pharmacyService;
    }

    public function mount($orgId)
    {
        $this->org_id = $orgId;
        $this->organization = Organization::findOrFail($this->org_id);
        $this->pharmacy_id = $this->pharmacyService->generatePharmacyId($this->org_id);
    }

    public function render()
    {
        return view('livewire.admin.organization.pharmacy.add-pharmacy');
    }

    #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function removeImage()
    {
        $this->pharmacy_logo = null;
    }

    public function resetInput()
    {
        $this->reset([ 
            'pharmacy_name', 'pharmacy_id', 'pharmacy_address', 'pharmacy_license_number', 
            'pharmacy_gst_num', 'pharmacy_contact_person_name', 'pharmacy_contact_person_number', 
            'pharmacy_contact_person_email', 'pharmacy_logo', 'pharmacy_opening_time', 
            'pharmacy_closing_time', 'status' 
        ]);

        $this->status = false;
        $this->pharmacy_id = $this->pharmacyService->generatePharmacyId($this->org_id);
        $this->resetErrorBag();
        $this->resetValidation();
        
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        Flux::modal('add-pharmacy')->close();
    }

    public function messages(){

        return[

            'pharmacy_name.required'                  => 'Pharmacy Name field is required.',
            'pharmacy_address.required'               => 'Pharmacy Address field is requird.',
            'pharmacy_license_number.required'        => 'Pharmacy License field is required.',
            'pharmacy_gst_num.required'               => 'Pharmacy GST field is required.',
            'pharmacy_contact_person_name.required'   => 'Contact Person Name field is required.',
            'pharmacy_contact_person_number.required' => 'Contact Person Number field is reuired.',
            'pharmacy_contact_person_email.required'  => 'Contact Person Email field is required',
            'pharmacy_logo.required'                  => 'Pharmacy Logo field is required.',
            'pharmacy_logo.image'                     => 'Only Image files are allowed.',
            'pharmacy_logo.max'                       => 'The logo may not be greater than 2MB.',
            'pharmacy_opening_time.required'          => 'Pharmacy Opening Time field is required.',
            'pharmacy_closing_time.required'          => 'Pharmacy Closing Time field is required.'

        ];

    }

    public function addPharmacy()
    {
        $this->validate();
        $pharmacyName = $this->pharmacy_name;
        $data = [
            'pharmacy_name'                  => $this->pharmacy_name,
            'pharmacy_id'                    => $this->pharmacy_id,
            'pharmacy_address'               => $this->pharmacy_address,
            'pharmacy_license_number'        => $this->pharmacy_license_number,
            'pharmacy_gst_num'               => $this->pharmacy_gst_num,
            'pharmacy_contact_person_name'   => $this->pharmacy_contact_person_name,
            'pharmacy_contact_person_number' => $this->pharmacy_contact_person_number,
            'pharmacy_contact_person_email'  => $this->pharmacy_contact_person_email,
            'pharmacy_opening_time'          => $this->pharmacy_opening_time,
            'pharmacy_closing_time'          => $this->pharmacy_closing_time,
            'status'                         => $this->status,
            'organization_id'                => $this->org_id
        ];

        $this->pharmacyService->createPharmacy($data, $this->pharmacy_logo);

        Flux::modal('add-pharmacy')->close();
        
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Pharmacy '.$pharmacyName.' added successfully!'
        );
        $this->dispatch('relodphar');
    }

}
