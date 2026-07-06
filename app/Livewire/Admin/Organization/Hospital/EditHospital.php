<?php

namespace App\Livewire\Admin\Organization\Hospital;

use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Services\HospitalService;
use App\Models\Pharmacy;
use App\Models\Diagnostic;


class EditHospital extends Component
{
     use WithFileUploads;
    public $hospital_name;
    public $hospital_subtitle;
    public $hospital_about;
    public $hospital_address;
    public $hospital_logo;
    public $hospital_admin_name;
    public $hospital_admin_contact;
    public $hospital_admin_email;
    public $hospital_admin_address;
    public $hospital_admin_longitude;
    public $hospital_admin_latitude;
    public $hospital_id;
    public ?string $original_hospital_admin_contact = null;
    public ?string $original_hospital_admin_email = null;
    public $status = false;
    public $old_hospital_logo;
    public $remove_image = false;
    protected $hospitalService;
    public $pharmacies = [];
    public $diagnosticCenters = [];
    public array $selected_pharmacy_ids = [];
    public ?int $selected_diagnostic_id = null; 
    public $is_24_hours_available = false;
    public $ambulance_available = false;
    public $ambulance_number;
    public function boot(HospitalService $hospitalService)
    {
        $this->hospitalService = $hospitalService;
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.edit-hospital');
    }

    #[On('edit')]
    public function EditHospital($id)
    {
        // Reset all fields first
        $this->resetInput();
        
        // Load hospital data
        $data = $this->hospitalService->findHospital($id);

        $this->hospital_id = (int) $id;

        $this->hospital_name             = $data->name;
        $this->hospital_subtitle         = $data->subtitle;
        $this->hospital_about            = $data->about;
        $this->hospital_address          = $data->address;
        $this->old_hospital_logo         = $data->logo;
        $this->hospital_admin_name       = $data->admin_name;
        $this->hospital_admin_contact    = $this->normalizeAdminContact($data->admin_contact);
        $this->original_hospital_admin_contact = $this->hospital_admin_contact;
        $this->hospital_admin_email      = trim((string) $data->admin_email);
        $this->original_hospital_admin_email = strtolower($this->hospital_admin_email);
        $this->hospital_admin_address    = $data->admin_address;
        $this->hospital_admin_longitude  = $data->admin_longitude;
        $this->hospital_admin_latitude   = $data->admin_latitude;
        $this->status                    = $data->status === 'active';
        $this->selected_pharmacy_ids     = $data->pharmacy_ids ?? [];
        $this->selected_diagnostic_id    = $data->diagnostic_center_id;
        $this->is_24_hours_available    = (bool) $data->is_24_hours_available;
        $this->ambulance_available      = (bool) $data->ambulance_available;
        $this->ambulance_number         = $data->ambulance_number;
        $this->remove_image = false;
        $this->hospital_logo = null;

        $this->pharmacies = Pharmacy::where('organization_id',$data->organization_id)->get();
    
        $this->diagnosticCenters = Diagnostic::where('organization_id',$data->organization_id)->get();

        Flux::modal('edit-hospital')->show();
    }

    public function togglePharmacy($id)
    {
        if (in_array($id, $this->selected_pharmacy_ids)) {
            $this->selected_pharmacy_ids =
                array_values(array_diff($this->selected_pharmacy_ids, [$id]));
        } else {
            $this->selected_pharmacy_ids[] = $id;
        }
    }

    public function updatedAmbulanceAvailable($value)
    {
        if (! $value) {
            $this->ambulance_number = null;
        }
    }

    public function removeImage()
    {
        $this->hospital_logo = null;
        $this->remove_image = true;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->hospital_logo = null;
        $this->remove_image = false;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

     #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function resetInput()
    {
        $this->reset(['hospital_name', 'hospital_subtitle', 'hospital_about', 'hospital_address', 'hospital_logo', 'hospital_admin_name','hospital_admin_contact','hospital_admin_email',
            'hospital_admin_address' ,'hospital_admin_longitude' ,'hospital_admin_latitude','status', 'selected_pharmacy_ids', 'selected_diagnostic_id', 'old_hospital_logo', 'remove_image', 'is_24_hours_available', 'ambulance_available', 'ambulance_number', 'hospital_id', 'original_hospital_admin_contact', 'original_hospital_admin_email']);
        $this->status = false;
        $this->remove_image = false;
        $this->is_24_hours_available = false;
        $this->ambulance_available = false;
        $this->ambulance_number = null;
        $this->resetErrorBag();
        $this->resetValidation();
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-hospital')->close();
        $this->dispatch('relodHos');
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
            'hospital_admin_email.email'        => 'Only valide email',
            'hospital_admin_email.unique'       => 'Email already exists.',
            'hospital_admin_email.required'     => 'Email field is required',
            'hospital_admin_address.required'   => 'Address field is required',
            'hospital_admin_longitude.required' => 'Longitude field is required',
            'hospital_admin_latitude.required'  => 'Latitude field is required',
            'selected_pharmacy_ids.required'    => 'Pharmacy field is required',
            'selected_diagnostic_id.required'   => 'Diagnostic Center field is required',
            'is_24_hours_available.boolean'    => 'Is 24 Hours Available must be true or false',
            'ambulance_number.required_if'     => 'Ambulance number is required when ambulance is available.',
            
        ];
    }

    public function updateHospital()
    {
        if (! $this->hospital_id) {
            $this->dispatch('toast', type: 'error', message: 'Hospital record not found. Please reopen the edit form.');

            return;
        }

        $this->hospital_admin_contact = $this->normalizeAdminContact($this->hospital_admin_contact);
        $this->hospital_admin_email = strtolower(trim((string) $this->hospital_admin_email));

        $contactRules = ['required', 'digits:10'];
        if ($this->hospital_admin_contact !== $this->original_hospital_admin_contact) {
            $contactRules[] = Rule::unique('hospitals', 'admin_contact')->ignore((int) $this->hospital_id);
        }

        $emailRules = ['required', 'email'];
        if ($this->hospital_admin_email !== $this->original_hospital_admin_email) {
            $emailRules[] = Rule::unique('hospitals', 'admin_email')->ignore((int) $this->hospital_id);
        }

        $this->validate([
            'hospital_name'            => 'required',
            'hospital_subtitle'        => 'required',
            'hospital_about'           => 'required',
            'hospital_address'         => 'required',
            'hospital_admin_name'      => 'required',
            'hospital_admin_contact'   => $contactRules,
            'hospital_admin_email'     => $emailRules,
            'hospital_admin_address'   => 'required',
            'hospital_admin_longitude' => 'required|numeric|between:-180,180',
            'hospital_admin_latitude'  => 'required|numeric|between:-90,90',
            'selected_pharmacy_ids'    => 'required',
            'selected_diagnostic_id'   => 'required',
            'is_24_hours_available'   => 'boolean',
            'ambulance_available'     => 'boolean',
            'ambulance_number'        => 'nullable|required_if:ambulance_available,true|string|max:20',
        ]);

        $hospitalName = $this->hospital_name;

        $data = [
            'name'            => $this->hospital_name,
            'subtitle'        => $this->hospital_subtitle,
            'about'           => $this->hospital_about,
            'address'         => $this->hospital_address,
            'admin_name'      => $this->hospital_admin_name,
            'admin_contact'   => $this->hospital_admin_contact,
            'admin_email'     => $this->hospital_admin_email,
            'admin_address'   => $this->hospital_admin_address,
            'admin_longitude' => $this->hospital_admin_longitude,
            'admin_latitude'  => $this->hospital_admin_latitude,
            'status'                   => $this->status,
            'pharmacy_ids'             => $this->selected_pharmacy_ids,
            'diagnostic_center_id'     => $this->selected_diagnostic_id,
            'is_24_hours_available'    => $this->is_24_hours_available,
            'ambulance_available'    => $this->ambulance_available,
            'ambulance_number'         => $this->ambulance_available ? $this->ambulance_number : null,

        ];

        // Handle image removal
        if ($this->remove_image && !$this->hospital_logo) {
            $data['logo'] = null;
        }

        $this->hospitalService->updateHospital($this->hospital_id, $data, $this->hospital_logo);
        
        $this->resetInput();
        
        // $this->dispatch('toast', text: 'Hospital updated successfully!', variant: 'success');
        Flux::modal('edit-hospital')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Hospital '.$hospitalName.' updated successfully!'
        );
        $this->dispatch('relodHos');
    }

    protected function normalizeAdminContact(mixed $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return substr($digits, 0, 10);
    }

}
