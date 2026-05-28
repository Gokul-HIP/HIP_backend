<?php

namespace App\Livewire\Admin\Doctor;

use Livewire\Component;
use Livewire\Attributes\On;
use App\Services\DoctorProfileService;
use Flux\Flux;
use Livewire\WithFileUploads;
use App\Models\Organization;
use App\Models\Hospital;
use App\Models\MasterQualification;
use App\Models\SpecialitiesMaster;
use App\Livewire\Admin\Doctor\Concerns\ManagesDoctorDiseaseSelect;
use Illuminate\Support\Facades\Log;

class EditDoctor extends Component
{
    use WithFileUploads;
    use ManagesDoctorDiseaseSelect;

    public $name;
    public $mobile_number;
    public $qualifications = [];
    public $qualificationOptions = [];
    public $working_since;
    public $email;
    public $publications;
    public $achievements;
    public $doctor_image;
    public $gender;
    public $hospital_ids = [];
    public $organization_id;
    public $scoped_organization_id;
    public $speciality = [];
    public $assigned_diseases = [];
    public $disease_data = [];
    public $status = false;
    public $doctor_id;
    public $old_doctor_image;
    public $remove_image = false;
    public $organizations = [];
    public $hospitals = [];
    public $new_qualification;
    public $new_qualification_description;
    public $speciality_data;
    public $about_doctor;
    public $consultation_fee;
    protected $listeners = [
        'qualification-added' => 'onQualificationAdded',
    ];

    protected $doctorProfileService;

    public function boot(DoctorProfileService $doctorProfileService)
    {
        $this->doctorProfileService = $doctorProfileService;
    }

    public function onQualificationAdded($id)
    {
        $this->qualificationOptions = MasterQualification::select('id', 'name')->orderBy('name')->get()->toArray();

        if (!in_array($id, $this->qualifications)) {
            $this->qualifications[] = $id;
        }    
    }

    public function openAddQualificationModal()
    {
        Flux::modal('add-qualification')->show();
    }

    public function mount($organization_id = null)
    {
        if ($organization_id) {
            $this->organization_id = (int) $organization_id;
            $this->scoped_organization_id = (int) $organization_id;
        }

        $this->speciality_data = SpecialitiesMaster::select('id', 'name')->orderBy('name')->get()->toArray();

        $this->organizations = Organization::select('id', 'name')->get();

        $this->qualificationOptions = MasterQualification::select('id', 'name')->orderBy('name')->get()->toArray();

        $this->loadHospitals();
    }

    public function loadHospitals()
    {
        if (!$this->organization_id) {
            $this->hospitals = [];
            return;
        }

        $this->hospitals = Hospital::where('organization_id', $this->organization_id)->select('id', 'name')->orderBy('name')->get()->toArray();

    }

    public function updatedOrganizationId()
    {
        $this->hospital_ids = [];
        $this->loadHospitals();
    }

    #[On('edit-doctor')]
    public function editDoctor($id)
    {
        $this->doctor_id = $id;
        $doctor = $this->doctorProfileService->findDoctor($id);
        $this->name = $doctor->name;
        $this->mobile_number = $doctor->mobile_number;
        $this->qualifications = collect($doctor->qualifications ?? [])
            ->map(fn($id) => (string)$id)->unique()->values()->toArray();
        $this->working_since = $doctor->working_since;
        $this->email = $doctor->email;
        $this->publications = $doctor->publications;
        $this->achievements = $doctor->achievements;
        $this->hospital_ids = collect($doctor->hospital_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values()
            ->toArray();
        $this->organization_id = $doctor->organization_id ?: $this->scoped_organization_id;
        $this->gender = $doctor->gender;
        $this->speciality = collect($doctor->speciality ?? [])
            ->map(fn($id) => (string)$id)->unique()->values()->toArray();
        $this->assigned_diseases = collect($doctor->assigned_diseases ?? [])
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values()
            ->toArray();
        $this->disease_search = '';
        $this->status = $doctor->status === 'active';
        $this->old_doctor_image = $doctor->doctor_image;
        $this->doctor_image = null;
        $this->remove_image = false;
        $this->about_doctor = $doctor->about_doctor;
        $this->consultation_fee = $doctor->consultation_fee;
        Log::info('Edit Doctor - Organization ID:', ['org_id' => $this->organization_id, 'doctor_id' => $id]);
        $this->loadHospitals();

        $validHospitalIds = collect($this->hospitals)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->hospital_ids = array_values(array_intersect($this->hospital_ids, $validHospitalIds));

        Flux::modal('edit-doctor')->show();
        $this->dispatch('relod-doctor');

    }

    public function selectOrganization($organizationId)
    {
        $this->organization_id = (int) $organizationId;
        $this->hospital_ids = [];
        $this->loadHospitals();
        $this->dispatch('organization-selected');
    }   

    public function removeImage()
    {
        $this->doctor_image = null;
        $this->remove_image = true;
    }

    public function restoreImage()
    {
        $this->doctor_image = null;
        $this->remove_image = false;
    }

    public function resetInput()
    {
        $this->reset(['name', 'mobile_number', 'qualifications', 'working_since', 'email', 'publications', 'achievements', 'doctor_image', 'gender', 'hospital_ids', 
        'organization_id', 'speciality', 'assigned_diseases', 'disease_search', 'status', 'old_doctor_image', 'remove_image', 'about_doctor', 'consultation_fee']);
        $this->remove_image = false;
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-doctor')->close();
        $this->dispatch('relod-doctor');
    }

    public function messages()
    {
        return [

            'name.required'   => 'Doctor name field is required.',
            'doctor_image.required'  => 'Doctor image field is required.',
            'doctor_image.image'     => 'Doctor image must be an image.',
            'doctor_image.max'       => 'Doctor image must be less than 2MB.',
            'speciality.required'    => 'Speciality field is required.',
            'mobile_number.required' => 'Mobile number field is required.',
            'mobile_number.numeric'  => 'Mobile number must be a number.',
            'mobile_number.digits'   => 'Mobile number must be 10 digits.',
            'mobile_number.unique'   => 'Mobile number already exists.',
            'gender.required'        => 'Gender field is required.',
            'about_doctor.required'  => 'About doctor field is required.',
        ];
    }

    public function editDoctorProfile(){

        $this->validate([

            'name'   => 'required',
            'doctor_image'  => 'nullable|image|max:2048',
            'mobile_number' => 'required|numeric|digits:10|unique:doctors,mobile_number,' . $this->doctor_id,
            'gender'        => 'required',
            'speciality'    => 'required|array',
            'email'         => 'nullable|email|unique:doctors,email,' . $this->doctor_id,
            'publications'  => 'nullable',
            'achievements'  => 'nullable',
            'qualifications' => 'nullable|array',
            'working_since' => 'nullable',
            'hospital_ids'   => 'nullable|array',
            'hospital_ids.*' => 'integer|exists:hospitals,id',
            'organization_id' => 'nullable',
            'about_doctor'       => 'required',
            'assigned_diseases'  => 'nullable|array',
            'assigned_diseases.*'=> 'integer|exists:diseases,id',
            'consultation_fee'  => 'nullable',
        ]);

        $data = [
            'name'             => $this->name,
            'mobile_number'    => $this->mobile_number,
            'qualifications'   => $this->qualifications,
            'working_since'    => $this->working_since,
            'email'            => $this->email,
            'publications'     => $this->publications,
            'achievements'     => $this->achievements,
            'gender'           => $this->gender,
            'speciality'       => $this->speciality,
            'status'           => $this->status,
            'hospital_ids'     => $this->normalizedHospitalIds(),
            'organization_id'  => $this->scoped_organization_id ?: $this->organization_id ?: $this->doctorProfileService->findDoctor($this->doctor_id)->organization_id,
            'about_doctor'      => $this->about_doctor,
            'assigned_diseases' => $this->normalizedDiseaseIds(),
            'consultation_fee'  => $this->consultation_fee,
        ];

        $doctorUp = $this->doctorProfileService->updateDoctor(
            $this->doctor_id,
            $data,
            $this->doctor_image,
            $this->remove_image
        );

        $this->old_doctor_image = $doctorUp->doctor_image;
        $this->remove_image = false;

        $this->resetInput();
        $this->dispatch('reset-file-input');
        Flux::modal('edit-doctor')->close();
        $this->dispatch('relod-doc');
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Doctor '.$doctorUp->name.' updated successfully!'
        );

    }

    public function addQualification()
    {
        $this->validate([
            'new_qualification' => 'required|string|max:255',
            'new_qualification_description' => 'nullable|string',
        ]);

        $qualification  = MasterQualification::create([
            'name' => $this->new_qualification,
            'description' => $this->new_qualification_description,
        ]);

        $this->qualificationOptions = MasterQualification::orderBy('name')->get();

        $this->qualifications[] = $qualification->id;

        $this->reset([
            'new_qualification',
            'new_qualification_description'
        ]);
        Flux::modal('add-qualification')->close();

    }

    private function normalizedHospitalIds(): ?array
    {
        if (empty($this->hospital_ids)) {
            return null;
        }

        $orgId = $this->scoped_organization_id ?: $this->organization_id;
        if (!$orgId) {
            return null;
        }

        $ids = Hospital::query()
            ->where('organization_id', $orgId)
            ->whereIn('id', array_map('intval', $this->hospital_ids))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        return $ids ?: null;
    }

    private function normalizedDiseaseIds(): ?array
    {
        if (empty($this->assigned_diseases)) {
            return null;
        }

        $ids = collect($this->assigned_diseases)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return $ids ?: null;
    }

    public function render()
    {
        return view('livewire.admin.doctor.edit-doctor');
    }
}
