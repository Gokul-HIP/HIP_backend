<?php

namespace App\Livewire\Admin\Organization\Hospital\Specialitie;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Hospital;
use App\Services\SpecialitieService;
use Flux\Flux;
use Illuminate\Support\Str;
use App\Models\Speciality;
use App\Models\SpecialitiesMaster;

class AddSpecialitie extends Component
{
    use WithFileUploads;

    public $speciality_name;
    public $speciality_code;
    public $speciality_description;
    public $department_category;
    public $associated_procedures;
    public $status = false;
    public $speciality_logo;
    public $organization_id;
    public $hospital_id;
    public $hospital;
    public $specialities;
    public $speciality_master_id;

    protected $specialitieService;

    public function boot(SpecialitieService $specialitieService)
    {
        $this->specialitieService = $specialitieService;
    }

    public function mount($hospitalId)
    {
        $this->hospital_id = $hospitalId;

        $hospital = Hospital::find($hospitalId);
        if (!$hospital) {
            abort(404, 'Hospital not found');
        }

        $this->specialities = SpecialitiesMaster::select('id', 'name')->orderBy('name')->get()->toArray();

        $this->hospital = $hospital;
        $this->organization_id = $hospital->organization_id;

        $this->generateSpecialityCode();
    }

    public function generateSpecialityCode()
    {
        do {
            $number = rand(1000, 9999);
            $code = Str::slug($this->hospital->name, '-')
                . '-SPECIALITY-' . date('Y') . '-' . $number;
        } while (Speciality::where('speciality_code', $code)->exists());

        $this->speciality_code = strtoupper($code);
    }

    public function resetInput()
    {
        $this->reset([
            'speciality_name',
            'speciality_code',
            'speciality_description',
            'department_category',
            'associated_procedures',
            'status',
            'speciality_logo'
        ]);
        $this->status = false;
        $this->generateSpecialityCode();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->removeImage();
        // Reset file input in frontend (same pattern as pharmacy add)
        $this->dispatch('reset-file-input');
    }

    public function removeImage()
    {
        $this->speciality_logo = null;

    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-specialitie')->close();
    }

    public function addSpeciality()
    {
        $this->validate([
            'speciality_name' => 'required|string|max:255',
            'speciality_description' => 'required|string',
            'department_category' => 'required|string',
            'speciality_logo' => 'required|image|max:2048'
        ]);

        $specialityName = $this->speciality_name;
        $statusValue = $this->status ? 'active' : 'inactive';

        $extension = $this->speciality_logo->getClientOriginalExtension();
        $imageName = Str::uuid() . '_' . hash('sha256', time()) . '.' . $extension;
        $this->speciality_logo->storeAs('speciality', $imageName, 'public');

        $specialityMasterId = SpecialitiesMaster::where('name', $this->department_category)->first()->id;

        $specialityData = [
            'speciality_name' => $this->speciality_name,
            'speciality_code' => $this->speciality_code,
            'speciality_description' => $this->speciality_description,
            'speciality_master_id' => $specialityMasterId,
            'department_category' => $this->department_category,
            'status' => $statusValue,
            'speciality_logo' => $imageName,
            'hospital_id' => $this->hospital_id,
            'organization_id' => $this->organization_id,
        ];

        try {
            $this->specialitieService->createSpeciality($specialityData);
        } catch (\Illuminate\Database\QueryException $e) {
            // Duplicate entry
            if ($e->errorInfo[1] == 1062) {

                // Regenerate code and retry
                $this->generateSpecialityCode();
                $specialityData['speciality_code'] = $this->speciality_code;

                $this->specialitieService->createSpeciality($specialityData);
            } else {
                throw $e; 
            }
        }

        $this->resetInput();
        Flux::modal('add-specialitie')->close();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Speciality ' . $specialityName . ' added successfully!'
        );

        $this->dispatch('relodSpe');
    }


    public function messages()
    {
        return [
            'speciality_name.required' => 'Speciality name field is required',
            'speciality_description.required' => 'Speciality description field is required',
            'department_category.required' => 'Department category field is required',
            'speciality_logo.required' => 'Speciality logo field is required',
            'speciality_logo.image' => 'Speciality logo must be an image',
            'speciality_logo.max' => 'Speciality logo may not be greater than 2MB',
        ];
    }

    public function render()
    {
        return view('livewire.admin.organization.hospital.specialitie.add-specialitie');
    }
}

