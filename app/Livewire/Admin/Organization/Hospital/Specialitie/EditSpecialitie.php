<?php

namespace App\Livewire\Admin\Organization\Hospital\Specialitie;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hospital;
use App\Services\SpecialitieService;
use Livewire\Attributes\On;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\SpecialitiesMaster;

class EditSpecialitie extends Component
{
    use WithFileUploads;

    public $speciality_id;
    public $speciality_name;
    public $speciality_code;
    public $speciality_description;
    public $department_category;
    public $status = false;
    public $speciality_logo;
    public $existing_logo;
    public $remove_image = false;
    public $organization_id;
    public $hospital_id;
    public $hospital;
    public $specialities;
    protected $specialitieService;

    public function boot(SpecialitieService $specialitieService)
    {
        $this->specialitieService = $specialitieService;
    }

    public function mount()
    {
        $this->specialities = SpecialitiesMaster::select('id', 'name')->orderBy('name')->get()->toArray();
    }

    #[On('edit')]
    public function edit($id)
    {
        $this->speciality_id = $id;
        $speciality = $this->specialitieService->findSpeciality($id);
        $this->hospital_id = $speciality->hospital_id;
        $this->organization_id = $speciality->organization_id;
        $this->hospital = Hospital::find($this->hospital_id);
        
        $this->speciality_name = $speciality->speciality_name;
        $this->speciality_code = $speciality->speciality_code;
        $this->speciality_description = $speciality->speciality_description;
        $this->department_category = $speciality->specialityMaster->name;
        $this->status = $speciality->status === 'active';
        $this->existing_logo = $speciality->speciality_logo;
        $this->speciality_logo = null; // Reset new upload

        Flux::modal('edit-specialitie')->show();
    }

    public function resetInput()
    {
        $this->reset([
            'speciality_id',
            'speciality_name',
            'speciality_code',
            'speciality_description',
            'department_category',
            'status',
            'speciality_logo',
            'existing_logo',
            'remove_image',
        ]);
        $this->status = false;
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        // Notify frontend to reset file input (same pattern as pharmacy edit)
        $this->dispatch('reset-file-input');
    }

    public function removeImage()
    {
        $this->speciality_logo = null;
        $this->remove_image = true;
        // Reset file input on frontend
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->speciality_logo = null;
        $this->remove_image = false;
        // Reset file input on frontend
        $this->dispatch('reset-file-input');
    }

    /**
     * Aliases for Livewire calls that might use snake_case.
     * This avoids MethodNotFoundException for remove_image / restore_image.
     */
    public function remove_image()
    {
        $this->removeImage();
    }

    public function restore_image()
    {
        $this->restoreImage();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-specialitie')->close();
    }

    public function updateSpeciality()
    {
        $this->validate([
            'speciality_name' => 'required|string|max:255',
            'speciality_description' => 'required|string',
            'department_category' => 'required|string',
            'speciality_logo' => 'nullable|image|max:2048'
        ]);

        $imageName = $this->existing_logo; // Keep existing image by default

        // If the existing image is marked for removal and no new image is uploaded
        if ($this->remove_image && !$this->speciality_logo) {
            if ($this->existing_logo && Storage::disk('public')->exists('speciality/' . $this->existing_logo)) {
                Storage::disk('public')->delete('speciality/' . $this->existing_logo);
            }
            $imageName = null;
        }

        // If new image is uploaded, replace the old one
        if ($this->speciality_logo) {
            // Delete old image if exists
            if ($this->existing_logo && Storage::disk('public')->exists('speciality/' . $this->existing_logo)) {
                Storage::disk('public')->delete('speciality/' . $this->existing_logo);
            }

            $extension = $this->speciality_logo->getClientOriginalExtension();
            $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
            $this->speciality_logo->storeAs('speciality', $imageName, 'public');
        }

        $statusValue = $this->status ? 'active' : 'inactive';

        $specialityName = $this->speciality_name;
        $specialityMasterId = SpecialitiesMaster::where('name', $this->department_category)->first()->id;
        
        $specialityData = [
            'speciality_name' => $this->speciality_name,
            'speciality_code' => $this->speciality_code,
            'speciality_description' => $this->speciality_description,
            'department_category' => $this->department_category,
            'status' => $statusValue,
            'speciality_logo' => $imageName,
            'speciality_master_id' => $specialityMasterId,
        ];

        $this->specialitieService->updateSpeciality($this->speciality_id, $specialityData);

        $this->resetInput();
        Flux::modal('edit-specialitie')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Speciality '.$specialityName.' updated successfully!'
        );
        $this->dispatch('relodSpe');

    }

    public function messages()
    {
        return [
            'speciality_name.required' => 'Speciality name field is required',
            'speciality_description.required' => 'Speciality description field is required',
            'department_category.required' => 'Department category field is required',
            'speciality_logo.image' => 'Speciality logo must be an image',
            'speciality_logo.max' => 'Speciality logo may not be greater than 2MB',
        ];
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.specialitie.edit-specialitie');
    }
}

