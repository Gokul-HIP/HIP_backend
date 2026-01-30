<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile;

use Livewire\Component;
use App\Models\Hospital;
use Illuminate\Support\Facades\Auth;

class HospitalProfile extends Component
{
    public $progressPercentage = 0;
    public $basic_details_completed = false;
    public $location_completed = false;
    public $capacity_completed = false;
    public $medical_completed = false;
    public $contact_completed = false;
    public $steps = [];
    public $onboarding_status = 'draft';

    public function mount()
    {
        $hospital = Hospital::find(Auth::user()->hospital->id);

        $this->onboarding_status = $hospital->onboarding_status ?? 'draft';
        


        $completed = collect([
            $hospital->basic_details_completed,
            $hospital->location_completed,
            $hospital->capacity_completed,
            $hospital->medical_completed,
            $hospital->contact_completed,
        ])->filter()->count();

        $this->basic_details_completed = (bool)$hospital->basic_details_completed;
        $this->location_completed = (bool)$hospital->location_completed;
        $this->capacity_completed = (bool)$hospital->capacity_completed;
        $this->medical_completed = (bool)$hospital->medical_completed;
        $this->contact_completed = (bool)$hospital->contact_completed;

        if ($this->onboarding_status === 'approved') {
            $this->basic_details_completed = true;
            $this->location_completed = true;
            $this->capacity_completed = true;
            $this->medical_completed = true;
            $this->contact_completed = true;
        }

        $this->steps = [
            [
                'key' => 'basic_details',
                'title' => 'Basic Hospital Details',
                'completed' => $this->basic_details_completed,
            ],
            [
                'key' => 'hospital_location',
                'title' => 'Hospital Location',
                'completed' => $this->location_completed,
            ],
            [
                'key' => 'hospital_capacity',
                'title' => 'Hospital Capacity',
                'completed' => $this->capacity_completed,
            ],
            [
                'key' => 'medical_compliance',
                'title' => 'Medical Compliance',
                'completed' => $this->medical_completed,
            ],
            [
                'key' => 'contact-details',
                'title' => 'Contact Details',
                'completed' => $this->contact_completed,
            ],
        ];
        
        $this->progressPercentage = ($completed / 5) * 100;

    }

    public function getStatusLabelProperty()
    {
        return match ($this->onboarding_status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Submitted',
        };
    }

    public function getProgressPercentageProperty()
    {
        $completed = collect($this->steps)->filter(fn($step) => $step['completed'])->count();
        return round(($completed / count($this->steps)) * 100);
    }

    public function navigateToStep($stepKey)
    {
        if(in_array($this->onboarding_status, ['approved', 'rejected'])) {
           return;
        }
        
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