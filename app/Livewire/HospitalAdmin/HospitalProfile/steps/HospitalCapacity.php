<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use App\Models\Organization;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Hospital;

class HospitalCapacity extends Component
{
    public $bed_strength;
    public $icu_beds;
    public $operating_theatres;
    public $ambulance_available;
    public $capacity_completed;
    public $basic_details_completed;
    public $location_completed;
    public $medical_completed;
    public $contact_completed;
    public $progressPercentage = 0;
    public $onboardingStatus = 'draft';
    public $currentStep = 3;
    public $totalSteps = 5;

    protected function rules()
    {
        return [
            'bed_strength' => 'required|integer|min:1',
            'icu_beds' => 'required|integer|min:0',
            'operating_theatres' => 'required|integer|min:0',
            'ambulance_available' => 'required|integer|min:0',
        ];
    }

    protected $messages = [
        'bed_strength.required' => 'Bed Strength is required.',
        'bed_strength.integer' => 'Bed Strength must be a number.',
        'bed_strength.min' => 'Bed Strength must be at least 1.',
        'icu_beds.required' => 'ICU Beds is required.',
        'icu_beds.integer' => 'ICU Beds must be a number.',
        'icu_beds.min' => 'ICU Beds must be zero or greater.',
        'operating_theatres.required' => 'Operating Theatres is required.',
        'operating_theatres.integer' => 'Operating Theatres must be a number.',
        'operating_theatres.min' => 'Operating Theatres must be zero or greater.',
        'ambulance_available.required' => 'Ambulance Available is required.',
        'ambulance_available.integer' => 'Ambulance Available must be a number.',
        'ambulance_available.min' => 'Ambulance Available must be zero or greater.',
    ];

    public function mount()
    {
        $hospital = Hospital::find(Auth::user()->hospital->id);

        $this->bed_strength = $hospital->bed_strength;
        $this->icu_beds = $hospital->icu_beds;
        $this->operating_theatres = $hospital->operating_theatres;
        $this->ambulance_available = $hospital->ambulance_available;
        $this->onboardingStatus = $hospital->onboarding_status;
        $completed = collect([
            $hospital->basic_details_completed,
            $hospital->location_completed,
            $hospital->capacity_completed,
            $hospital->medical_completed,
            $hospital->contact_completed,
        ])->filter()->count();

        $this->progressPercentage = ($completed / 5) * 100;

        $this->basic_details_completed = $hospital->basic_details_completed;
        $this->location_completed = $hospital->location_completed;
        $this->capacity_completed = $hospital->capacity_completed;
        $this->medical_completed = $hospital->medical_completed;
        $this->contact_completed = $hospital->contact_completed;
    }

    public function save()
    {
        $this->validate();

        $hospital = Hospital::find(Auth::user()->hospital->id);
        
        $capacityCompleted = !(
            empty($this->bed_strength ?? $hospital->bed_strength) ||
            empty($this->icu_beds ?? $hospital->icu_beds) ||
            empty($this->operating_theatres ?? $hospital->operating_theatres) ||
            empty($this->ambulance_available ?? $hospital->ambulance_available)
        );
        
        Hospital::where('id', Auth::user()->hospital->id)->update([
            'bed_strength' => $this->bed_strength,
            'icu_beds' => $this->icu_beds,
            'operating_theatres' => $this->operating_theatres,
            'ambulance_available' => $this->ambulance_available,
            'capacity_completed' => $capacityCompleted,
            'capacity_status' => 'submitted',
        ]);

        $this->dispatch('toast', type: 'success', message: 'Hospital capacity saved successfully!');
        
        // Redirect to next step (Medical Compliance)
        return redirect()->route('hospital.hospital-profile.medical_compliance');
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.hospital-capacity');
    }
}