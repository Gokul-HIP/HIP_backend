<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use App\Models\Organization;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Hospital;

class ContactDetails extends Component
{
    public $contact_person_name;
    public $contact_person_mobile;
    public $contact_person_email;
    public $emergency_contact_number;
    public $pincode;
    public $state;
    public $basic_details_completed;
    public $location_completed;
    public $capacity_completed;
    public $medical_completed;
    public $contact_completed;
    public $progressPercentage = 0;
    public $currentStep = 5;
    public $totalSteps = 5;

    protected function rules()
    {
        return [
            'contact_person_name' => 'required|string|max:255',
            'contact_person_mobile' => 'required|digits:10',
            'contact_person_email' => 'required|email|max:255',
            'emergency_contact_number' => 'required|digits:10',
            'pincode' => 'required|digits:6',
            'state' => 'required|string|max:100',
        ];
    }

    protected $messages = [
        'contact_person_name.required' => 'Contact Person Name is required.',
        'contact_person_mobile.required' => 'Contact Person Mobile is required.',
        'contact_person_mobile.digits' => 'Contact Person Mobile must be exactly 10 digits.',
        'contact_person_email.required' => 'Contact Person Email is required.',
        'contact_person_email.email' => 'Contact Person Email must be a valid email address.',
        'emergency_contact_number.required' => 'Emergency Contact Number is required.',
        'emergency_contact_number.digits' => 'Emergency Contact Number must be exactly 10 digits.',
        'pincode.required' => 'Pincode is required.',
        'pincode.digits' => 'Pincode must be exactly 6 digits.',
        'state.required' => 'State is required.',
    ];

    public function mount()
    {
       
        $hospital = Hospital::find(Auth::user()->hospital->id);

        $this->contact_person_name = $hospital->contact_person_name;
        $this->contact_person_mobile = $hospital->contact_person_mobile;
        $this->contact_person_email = $hospital->contact_person_email;
        $this->emergency_contact_number = $hospital->emergency_contact_number;
        $this->pincode = $hospital->pincode;
        $this->state = $hospital->state;

        $this->basic_details_completed = $hospital->basic_details_completed;
        $this->location_completed = $hospital->location_completed;
        $this->capacity_completed = $hospital->capacity_completed;
        $this->medical_completed = $hospital->medical_completed;
        $this->contact_completed = $hospital->contact_completed;

        $completed = collect([
            $hospital->basic_details_completed,
            $hospital->location_completed,
            $hospital->capacity_completed,
            $hospital->medical_completed,
            $hospital->contact_completed,
        ])->filter()->count();
        
        $this->progressPercentage = ($completed / 5) * 100;

    }

    public function save()
    {
        $this->validate();

        $hospital = Hospital::find(Auth::user()->hospital->id);

        $contactCompleted = !(
            empty($this->contact_person_name ?? $hospital->contact_person_name) ||
            empty($this->contact_person_mobile ?? $hospital->contact_person_mobile) ||
            empty($this->contact_person_email ?? $hospital->contact_person_email) ||
            empty($this->emergency_contact_number ?? $hospital->emergency_contact_number) ||
            empty($this->pincode ?? $hospital->pincode)
        );

        $data = [
            'contact_person_name' => $this->contact_person_name,
            'contact_person_mobile' => $this->contact_person_mobile,
            'contact_person_email' => $this->contact_person_email,
            'emergency_contact_number' => $this->emergency_contact_number,
            'pincode' => $this->pincode,
            'contact_completed' => $contactCompleted,
            'onboarding_status' => 'submitted',
        ];

        dd($data);

        $hospital->update($data);

        $this->dispatch('toast', type: 'success', message: 'Contact details saved');
        
        return redirect()->route('hospital.hospital-profile.index');
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.contact-details');
    }
}

