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
    public $basic_details_status = 'draft';
    public $location_status = 'draft';
    public $capacity_status = 'draft';
    public $medical_status = 'draft';
    public $contact_status = 'draft';
    public $comments;

    public function mount()
    {
        $hospital = Hospital::find(Auth::user()->hospital->id);

        $this->comments = $hospital->comments;

        $this->onboarding_status = $hospital->onboarding_status ?? 'draft';
        
        $this->basic_details_status = $hospital->basic_details_status;
        $this->location_status = $hospital->location_status;
        $this->capacity_status = $hospital->capacity_status;
        $this->medical_status = $hospital->medical_status;
        $this->contact_status = $hospital->contact_status;

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

        // if ($this->onboarding_status === 'approved') {
        //     $this->basic_details_completed = true;
        //     $this->location_completed = true;
        //     $this->capacity_completed = true;
        //     $this->medical_completed = true;
        //     $this->contact_completed = true;
        // } else if ($this->onboarding_status === 'rejected') {
        //     $this->basic_details_completed = false;
        //     $this->location_completed = false;
        //     $this->capacity_completed = false;
        //     $this->medical_completed = false;
        //     $this->contact_completed = false;
        // }

        $this->steps = [
            [
                'key' => 'basic_details',
                'title' => 'Basic Hospital Details',
                'completed' => $this->basic_details_completed,
                'status' => $this->basic_details_status,
            ],
            [
                'key' => 'hospital_location',
                'title' => 'Hospital Location',
                'completed' => $this->location_completed,
                'status' => $this->location_status,
            ],
            [
                'key' => 'hospital_capacity',
                'title' => 'Hospital Capacity',
                'completed' => $this->capacity_completed,
                'status' => $this->capacity_status,
            ],
            [
                'key' => 'medical_compliance',
                'title' => 'Medical Compliance',
                'completed' => $this->medical_completed,
                'status' => $this->medical_status,
            ],
            [
                'key' => 'contact-details',
                'title' => 'Contact Details',
                'completed' => $this->contact_completed,
                'status' => $this->contact_status,
            ],
        ];
        
        $this->progressPercentage = ($completed / 5) * 100;

    }

    public function getStatusLabelProperty()
    {
        return match ($this->onboarding_status) {
            'draft' => 'Incomplete',
            'submitted' => 'Submitted',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Draft',
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