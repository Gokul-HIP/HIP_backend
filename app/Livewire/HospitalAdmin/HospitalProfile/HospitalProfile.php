<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile;

use Livewire\Component;
use App\Models\Hospital;
use Illuminate\Support\Facades\Auth;

class HospitalProfile extends Component
{
    public $progressPercentage = 0;
    public $steps = [
        [
            'key' => 'basic_details',
            'title' => 'Basic Hospital Details',
            'completed' => false,
        ],
        [
            'key' => 'hospital_location',
            'title' => 'Hospital Location',
            'completed' => false,
        ],
        [
            'key' => 'hospital_capacity',
            'title' => 'Hospital Capacity',
            'completed' => false,
        ],
        [
            'key' => 'medical_compliance',
            'title' => 'Medical Compliance',
            'completed' => false,
        ],
        [
            'key' => 'contact_details',
            'title' => 'Contact Details',
            'completed' => false,
        ],
    ];

    public function mount()
    {
        $hospital = Hospital::find(Auth::user()->hospital->id);
        $completed = collect([
            $hospital->basic_details_completed,
            $hospital->location_completed,
            $hospital->capacity_completed,
            $hospital->medical_completed,
            $hospital->contact_completed,
        ])->filter()->count();
        
        $this->progressPercentage = ($completed / 5) * 100;
    }

    public function getProgressPercentageProperty()
    {
        $completed = collect($this->steps)->filter(fn($step) => $step['completed'])->count();
        return round(($completed / count($this->steps)) * 100);
    }

    public function navigateToStep($stepKey)
    {
        // Navigate to the specific step form
        return redirect()->route('hospital.hospital-profile.' . $stepKey);
    }

    public function submitForReview()
    {
        if ($this->progressPercentage === 100) {
            // Submit hospital profile for review
            session()->flash('message', 'Hospital profile submitted for review successfully!');
        } else {
            session()->flash('error', 'Please complete all steps before submitting.');
        }
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.hospital-profile');
    }
}