<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use App\Models\Organization;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Hospital;
use Illuminate\Support\Facades\DB;

class ContactDetails extends Component
{
    public $contact_person_name;
    public $contact_person_mobile;
    public $contact_person_email;
    public $emergency_contact_number;
    public $hospital_admin_pincode;
    public $state;
    public $basic_details_completed;
    public $location_completed;
    public $capacity_completed;
    public $medical_completed;
    public $contact_completed;
    public $progressPercentage = 0;
    public $currentStep = 5;
    public $totalSteps = 5;
    public $onboardingStatus = 'draft';
    protected function rules()
    {
        return [
            'contact_person_name' => 'required|string|max:255',
            'contact_person_mobile' => 'required|digits:10',
            'contact_person_email' => 'required|email|max:255',
            'emergency_contact_number' => 'required|digits:10',
            'hospital_admin_pincode' => 'required|string|max:100',
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
        'hospital_admin_pincode.required' => 'Hospital Admin Pincode is required.',
        'state.required' => 'State is required.',
    ];

    public function mount()
    {
       
        $hospital = Hospital::find(Auth::user()->hospital->id);

        $this->contact_person_name = $hospital->admin_name ?? '';
        $this->contact_person_mobile = $hospital->admin_contact ?? '';
        $this->contact_person_email = $hospital->admin_email ?? '';
        $this->emergency_contact_number = $hospital->admin_emergency_contact ?? '';
        $this->hospital_admin_pincode = $hospital->admin_pincode ?? '';
        $this->state = $hospital->state ?? 'Karnataka';
        $this->onboardingStatus = $hospital->onboarding_status;
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

        DB::beginTransaction();

        try{
            $hospital = Hospital::find(Auth::user()->hospital->id);

            $contactCompleted = !(
                empty($this->contact_person_name ?? $hospital->admin_name) ||
                empty($this->contact_person_mobile ?? $hospital->admin_contact) ||
                empty($this->contact_person_email ?? $hospital->admin_email) ||
                empty($this->emergency_contact_number ?? $hospital->admin_emergency_contact) ||
                empty($this->hospital_admin_pincode ?? $hospital->admin_pincode)
            );

            $completed = collect([
                $hospital->basic_details_completed,
                $hospital->location_completed,
                $hospital->capacity_completed,
                $hospital->medical_completed,
                $hospital->contact_completed,
            ])->filter(fn ($v) => (int)$v === 1)->count();

            if ($completed !== 5) {
                DB::rollBack();
            
                $this->dispatch('toast',
                    type: 'error',
                    message: 'Please fill all steps before submitting.'
                );
            
                return;
            }

            $data = [
                'admin_name' => $this->contact_person_name,
                'admin_contact' => $this->contact_person_mobile,
                'admin_email' => $this->contact_person_email,
                'admin_emergency_contact' => $this->emergency_contact_number,
                'admin_pincode' => $this->hospital_admin_pincode,
                'state' => $this->state,
                'contact_completed' => $contactCompleted,
                'contact_status' => 'submitted',
                'onboarding_status' => 'submitted',
                'updated_at' => now(),
            ];

            DB::table('hospitals')->where('id', Auth::user()->hospital->id)->update($data);

            DB::commit();

            $this->dispatch('toast', type: 'success', message: 'Contact details saved');
            
            return redirect()->route('hospital.hospital-profile.index');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', type: 'error', message: 'Failed to save contact details: ' . $e->getMessage());
            return;
        }
        
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.contact-details');
    }
}

