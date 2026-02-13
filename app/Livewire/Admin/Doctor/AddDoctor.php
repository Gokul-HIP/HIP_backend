<?php

namespace App\Livewire\Admin\Doctor;

use Livewire\Component;
use Livewire\WithFileUploads;
use Flux\Flux;
use App\Services\DoctorProfileService;
use App\Models\Hospital;
use App\Models\Organization;
use App\Models\MasterQualification;
use App\Models\SpecialitiesMaster;

class AddDoctor extends Component
{
    use WithFileUploads;

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
    public $speciality = [];
    public $status = false;
    public $organizations = [];
    public $hospitals = [];
    public $new_qualification;
    public $new_qualification_description;
    public $speciality_data;
    public $about_doctor;

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
        $this->qualificationOptions = MasterQualification::select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();

            if (!in_array($id, $this->qualifications)) {
                $this->qualifications[] = $id;
            }    
    }

    public function openAddQualificationModal()
    {
        Flux::modal('add-qualification')->show();
    }

    public function mount()
    {
        $this->speciality_data = SpecialitiesMaster::select('id', 'name')->orderBy('name')->get()->toArray();

        $this->organizations = Organization::select('id', 'name')->get();

        $this->qualificationOptions = MasterQualification::select('id', 'name')->orderBy('name')->get()->toArray(); 

    }

    public function selectOrganization($organizationId)
    {
        $this->organization_id = $organizationId;
        $this->hospital_ids = [];
        $this->hospitals = [];
        $this->loadHospitals();
        $this->dispatch('organization-selected');
    }

    public function loadHospitals()
    {
        if (!$this->organization_id) {
            $this->hospitals = [];
            return;
        }

        $this->hospitals = Hospital::where('organization_id', $this->organization_id)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->toArray();
            
    }

    public function toggleHospital($hospitalId)
    {
        if (in_array($hospitalId, $this->hospital_ids)) {
            
            $this->hospital_ids = array_values(
                array_diff($this->hospital_ids, [$hospitalId])
            );
        } else {
            
            $this->hospital_ids[] = $hospitalId;
        }
    }

    public function selectHospital($hospitalId)
    {
        $this->hospital_ids = $hospitalId;
        $this->dispatch('hospital-selected');
    }

    public function addDoctorProfile()
    {
        $this->validate([

            'name'     => 'required',
            'doctor_image'    => 'required|image|max:2048',
            'mobile_number'   => 'required|numeric|unique:doctors,mobile_number|digits:10',
            'gender'          => 'required',
            'speciality'      => 'required|array',
            'email'           => 'nullable|email|unique:doctors,email',
            'publications'    => 'nullable',
            'achievements'    => 'nullable',
            'qualifications'  => 'nullable|array',
            'working_since'   => 'nullable',
            'hospital_ids'    => 'required|array|min:1',
            'organization_id' => 'nullable',
            'about_doctor'    => 'required',
        ]);

        $data = [
            'name'             => $this->name,
            'gender'           => $this->gender,
            'speciality'       => $this->speciality,
            'working_since'    => $this->working_since,
            'email'            => $this->email,
            'publications'     => $this->publications,
            'mobile_number'    => $this->mobile_number,
            'qualifications'   => $this->qualifications,
            'achievements'     => $this->achievements,
            'status'           => $this->status,
            'hospital_ids'     => $this->hospital_ids,
            'organization_id'  => $this->organization_id,
            'about_doctor'     => $this->about_doctor,
        ];

        $doctor = $this->doctorProfileService->createDoctor($data, $this->doctor_image);

        $this->resetInput();
        Flux::modal('add-doctor')->close();
        $this->dispatch('relod-doc');
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Doctor '.$doctor->name.' added successfully!'
        );

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

    public function removeImage()
    {
        $this->doctor_image = null;
    }

    public function resetInput()
    {
        $this->reset([
            'name', 
            'mobile_number', 
            'qualifications', 
            'working_since', 
            'email', 
            'publications', 
            'achievements', 
            'doctor_image', 
            'gender', 
            'speciality',
            'status',
            'organization_id',
            'hospital_ids',
            'hospitals',
            'about_doctor',
        ]);

        $this->doctor_image = null;
        $this->resetErrorBag();
    }

    public function closeModal()
    {
        $this->resetInput();
        $this->dispatch('reset-file-input');
        Flux::modal('add-doctor')->close();
    }


    public function render()
    {
        return view('livewire.admin.doctor.add-doctor');
    }
}