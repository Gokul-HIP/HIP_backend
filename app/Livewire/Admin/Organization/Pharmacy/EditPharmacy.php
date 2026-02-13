<?php

namespace App\Livewire\Admin\Organization\Pharmacy;

use Livewire\Component;
use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Services\PharmacyService;


class EditPharmacy extends Component
{
    use WithFileUploads;

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

     #[Rule("required")]
    public $pharmacy_contact_person_number;

     #[Rule("required|email")]
    public $pharmacy_contact_person_email;
    public $pharmacy_logo;

     #[Rule("required")]
    public $pharmacy_opening_time;

     #[Rule("required")]
    public $pharmacy_closing_time;
    public $status = false;
    public $pharmacy_edit_id;
    public $old_pharmacy_logo;
    public $remove_image = false;

    protected $pharmacyService;

    public function boot(PharmacyService $pharmacyService)
    {
        $this->pharmacyService = $pharmacyService;
    }


    public function removeImage()
    {
        $this->pharmacy_logo = null;
        $this->remove_image = true;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->pharmacy_logo = null;
        $this->remove_image = false;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

     #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-pharmacy')->close();
    }

    public function resetInput()
    {
        $this->reset([ 'pharmacy_name','pharmacy_id','pharmacy_address','pharmacy_license_number','pharmacy_gst_num','pharmacy_contact_person_name'
            ,'pharmacy_contact_person_number','pharmacy_contact_person_email',
            'pharmacy_logo','pharmacy_opening_time','pharmacy_closing_time','status','old_pharmacy_logo','remove_image' ]);

        $this->status = false;
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
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
            'pharmacy_closing_time.required'          => 'Pharmacy Closing Time field is required'

        ];

    }

    #[On('editpharmacy')]
    public function editpharmacy($id)
    {
        // Reset all fields first
        $this->resetInput();
        
        // Load pharmacy data
        $data = $this->pharmacyService->findPharmacy($id);

        $this->pharmacy_edit_id = $id;

        $this->pharmacy_name                  = $data->name;
        $this->pharmacy_address               = $data->address;
        $this->pharmacy_id                    = $data->pharmacy_id;
        $this->pharmacy_license_number        = $data->license_number;
        $this->pharmacy_gst_num               = $data->gst_number;
        $this->pharmacy_contact_person_name   = $data->contact_person_name;
        $this->pharmacy_contact_person_number = $data->contact_person_number;
        $this->pharmacy_contact_person_email  = $data->contact_person_email;
        $this->old_pharmacy_logo              = $data->logo;
        $this->pharmacy_opening_time          = $data->opening_time;
        $this->pharmacy_closing_time          = $data->closing_time;
        $this->status                         = $data->status === 'active';

        Flux::modal('edit-pharmacy')->show();
    }

    public function updatePharmacy()
    {
        $this->validate([
            'pharmacy_name'                  => 'required',
            'pharmacy_address'               => 'required',
            'pharmacy_license_number'        => 'required',
            'pharmacy_gst_num'               => 'required',
            'pharmacy_contact_person_name'   => 'required',
            'pharmacy_contact_person_number' => 'required|digits:10|unique:pharmacies,contact_person_number,' . $this->pharmacy_edit_id,
            'pharmacy_contact_person_email'  => 'required|email|unique:pharmacies,contact_person_email,' . $this->pharmacy_edit_id,
        ]);

        $pharmacyName = $this->pharmacy_name;

        $data = [
            'name'                  => $this->pharmacy_name,
            'address'               => $this->pharmacy_address,
            'license_number'        => $this->pharmacy_license_number,
            'gst_number'               => $this->pharmacy_gst_num,
            'contact_person_name'   => $this->pharmacy_contact_person_name,
            'contact_person_number' => $this->pharmacy_contact_person_number,
            'contact_person_email'  => $this->pharmacy_contact_person_email,
            'opening_time'          => $this->pharmacy_opening_time,
            'closing_time'          => $this->pharmacy_closing_time,
            'status'                         => $this->status
        ];

        // Handle image removal
        if ($this->remove_image && !$this->pharmacy_logo) {
            $data['logo'] = null;
        }

        $this->pharmacyService->updatePharmacy($this->pharmacy_edit_id, $data, $this->pharmacy_logo);

        // Close modal and reset
        Flux::modal('edit-pharmacy')->close();
        $this->resetInput();
        
        // Dispatch success messages
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Pharmacy '.$pharmacyName.' updated successfully!'
        );
        $this->dispatch('relodphar');
    }

    public function render()
    {
        return view('livewire.admin.organization.pharmacy.edit-pharmacy');
    }
}
