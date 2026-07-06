<?php

namespace App\Livewire\HospitalAdmin\Hospitals;

use App\Models\Diagnostic;
use App\Models\Pharmacy;
use App\Services\HospitalService;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

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
    public $is_24_hours_available = false;
    public $ambulance_available = false;
    public $ambulance_number;
    public $old_hospital_logo;
    public $remove_image = false;
    protected $hospitalService;
    public $pharmacies = [];
    public $diagnosticCenters = [];
    public array $selected_pharmacy_ids = [];
    public ?int $selected_diagnostic_id = null;

    public function boot(HospitalService $hospitalService): void
    {
        $this->hospitalService = $hospitalService;
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospitals.edit-hospital');
    }

    #[On('edit')]
    public function editHospital($id): void
    {
        $this->resetInput();

        $data = $this->hospitalService->findHospital($id);

        $this->hospital_id = (int) $id;
        $this->hospital_name = $data->name;
        $this->hospital_subtitle = $data->subtitle;
        $this->hospital_about = $data->about;
        $this->hospital_address = $data->address;
        $this->old_hospital_logo = $data->logo;
        $this->hospital_admin_name = $data->admin_name;
        $this->hospital_admin_contact = $this->normalizeAdminContact($data->admin_contact);
        $this->original_hospital_admin_contact = $this->hospital_admin_contact;
        $this->hospital_admin_email = trim((string) $data->admin_email);
        $this->original_hospital_admin_email = strtolower($this->hospital_admin_email);
        $this->hospital_admin_address = $data->admin_address;
        $this->hospital_admin_longitude = $data->admin_longitude;
        $this->hospital_admin_latitude = $data->admin_latitude;
        $this->status = $data->status === 'active';
        $this->selected_pharmacy_ids = $data->pharmacy_ids ?? [];
        $this->selected_diagnostic_id = $data->diagnostic_center_id;
        $this->is_24_hours_available = (bool) $data->is_24_hours_available;
        $this->ambulance_available = (bool) $data->ambulance_available;
        $this->ambulance_number = $data->ambulance_number;
        $this->remove_image = false;
        $this->hospital_logo = null;

        $this->pharmacies = Pharmacy::where('organization_id', $data->organization_id)->get();
        $this->diagnosticCenters = Diagnostic::where('organization_id', $data->organization_id)->get();

        Flux::modal('edit-hospital')->show();
    }

    public function updatedAmbulanceAvailable($value): void
    {
        if (! $value) {
            $this->ambulance_number = null;
        }
    }

    public function togglePharmacy($id): void
    {
        if (in_array($id, $this->selected_pharmacy_ids)) {
            $this->selected_pharmacy_ids = array_values(array_diff($this->selected_pharmacy_ids, [$id]));
        } else {
            $this->selected_pharmacy_ids[] = $id;
        }
    }

    public function removeImage(): void
    {
        $this->hospital_logo = null;
        $this->remove_image = true;
        $this->dispatch('reset-file-input');
    }

    public function restoreImage(): void
    {
        $this->hospital_logo = null;
        $this->remove_image = false;
        $this->dispatch('reset-file-input');
    }

    #[On('statusChanged')]
    public function updateStatus($value): void
    {
        $this->status = $value;
    }

    public function resetInput(): void
    {
        $this->reset([
            'hospital_name',
            'hospital_subtitle',
            'hospital_about',
            'hospital_address',
            'hospital_logo',
            'hospital_admin_name',
            'hospital_admin_contact',
            'hospital_admin_email',
            'hospital_admin_address',
            'hospital_admin_longitude',
            'hospital_admin_latitude',
            'status',
            'selected_pharmacy_ids',
            'selected_diagnostic_id',
            'old_hospital_logo',
            'remove_image',
            'is_24_hours_available',
            'ambulance_available',
            'ambulance_number',
            'hospital_id',
            'original_hospital_admin_contact',
            'original_hospital_admin_email',
        ]);
        $this->status = false;
        $this->is_24_hours_available = false;
        $this->ambulance_available = false;
        $this->ambulance_number = null;
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('reset-file-input');
    }

    public function closeModal(): void
    {
        $this->resetInput();
        Flux::modal('edit-hospital')->close();
        $this->dispatch('relodHos');
    }

    public function updateHospital(): void
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
            'hospital_name' => 'required',
            'hospital_subtitle' => 'required',
            'hospital_about' => 'required',
            'hospital_address' => 'required',
            'hospital_admin_name' => 'required',
            'hospital_admin_contact' => $contactRules,
            'hospital_admin_email' => $emailRules,
            'hospital_admin_address' => 'required',
            'hospital_admin_longitude' => 'required|numeric|between:-180,180',
            'hospital_admin_latitude' => 'required|numeric|between:-90,90',
            'selected_pharmacy_ids' => 'required',
            'selected_diagnostic_id' => 'required',
            'ambulance_available' => 'boolean',
            'ambulance_number' => 'nullable|required_if:ambulance_available,true|string|max:20',
        ]);

        $hospitalName = $this->hospital_name;

        $data = [
            'name' => $this->hospital_name,
            'subtitle' => $this->hospital_subtitle,
            'about' => $this->hospital_about,
            'address' => $this->hospital_address,
            'admin_name' => $this->hospital_admin_name,
            'admin_contact' => $this->hospital_admin_contact,
            'admin_email' => $this->hospital_admin_email,
            'admin_address' => $this->hospital_admin_address,
            'admin_longitude' => $this->hospital_admin_longitude,
            'admin_latitude' => $this->hospital_admin_latitude,
            'status' => $this->status,
            'pharmacy_ids' => $this->selected_pharmacy_ids,
            'diagnostic_center_id' => $this->selected_diagnostic_id,
            'is_24_hours_available' => $this->is_24_hours_available,
            'ambulance_available' => $this->ambulance_available,
            'ambulance_number' => $this->ambulance_available ? $this->ambulance_number : null,
        ];

        if ($this->remove_image && !$this->hospital_logo) {
            $data['logo'] = null;
        }

        $this->hospitalService->updateHospital($this->hospital_id, $data, $this->hospital_logo);

        $this->resetInput();
        Flux::modal('edit-hospital')->close();
        $this->dispatch('toast', type: 'success', message: 'Hospital ' . $hospitalName . ' updated successfully!');
        $this->dispatch('relodHos');
    }

    protected function normalizeAdminContact(mixed $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        return substr($digits, 0, 10);
    }
}

