<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use App\Models\Organization;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use App\Models\Hospital;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MedicalCompliance extends Component
{
    use WithFileUploads;

    public $basic_details_completed;
    public $location_completed;
    public $capacity_completed;
    public $medical_completed;
    public $contact_completed;
    public $progressPercentage = 0;
    public $registration_certificate;
    public $ownership_proof;
    public $accreditation_certificate;
    public $fire_safety_certificate;
    public $insurance_policy_number;
    public $ownership_proof_doc;
    
    // Old file paths
    public $old_registration_certificate;
    public $old_ownership_proof;
    public $old_accreditation_certificate;
    public $old_fire_safety_certificate;
    public $old_ownership_proof_doc;
    
    // Remove flags
    public bool $remove_registration_certificate = false;
    public bool $remove_ownership_proof = false;
    public bool $remove_accreditation_certificate = false;
    public bool $remove_fire_safety_certificate = false;
    public bool $remove_ownership_proof_doc = false;
    
    public $currentStep = 4;
    public $totalSteps = 5;
    public $onboardingStatus = 'draft';
    protected function rules()
    {
        return [
            'registration_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB
            'ownership_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'accreditation_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'fire_safety_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'insurance_policy_number' => 'nullable|string|max:100',
            'ownership_proof_doc' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];
    }

    protected $messages = [
        'registration_certificate.mimes' => 'Registration certificate must be a PDF, JPG, JPEG, or PNG file.',
        'registration_certificate.max' => 'Registration certificate must not exceed 5MB.',
        'ownership_proof.mimes' => 'Ownership proof must be a PDF, JPG, JPEG, or PNG file.',
        'ownership_proof.max' => 'Ownership proof must not exceed 5MB.',
        'accreditation_certificate.mimes' => 'Accreditation certificate must be a PDF, JPG, JPEG, or PNG file.',
        'accreditation_certificate.max' => 'Accreditation certificate must not exceed 5MB.',
        'fire_safety_certificate.mimes' => 'Fire safety certificate must be a PDF, JPG, JPEG, or PNG file.',
        'fire_safety_certificate.max' => 'Fire safety certificate must not exceed 5MB.',
        'ownership_proof_doc.mimes' => 'Ownership proof document must be a PDF, JPG, JPEG, or PNG file.',
        'ownership_proof_doc.max' => 'Ownership proof document must not exceed 5MB.',
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

        $this->basic_details_completed = $hospital->basic_details_completed;
        $this->location_completed = $hospital->location_completed;
        $this->capacity_completed = $hospital->capacity_completed;
        $this->medical_completed = $hospital->medical_completed;
        $this->contact_completed = $hospital->contact_completed;
        $this->onboardingStatus = $hospital->onboarding_status;
        // Load existing file paths
        $this->old_registration_certificate = $hospital->registration_certificate;
        $this->old_ownership_proof = $hospital->ownership_proof;
        $this->old_accreditation_certificate = $hospital->accreditation_certificate;
        $this->old_fire_safety_certificate = $hospital->fire_safety_certificate;
        $this->old_ownership_proof_doc = $hospital->ownership_proof_doc;
        $this->insurance_policy_number = $hospital->insurance_policy_number;
    }
    
    public function removeRegistrationCertificate()
    {
        $this->remove_registration_certificate = true;
        $this->registration_certificate = null;
    }
    
    public function restoreRegistrationCertificate()
    {
        $this->remove_registration_certificate = false;
    }
    
    public function removeOwnershipProof()
    {
        $this->remove_ownership_proof = true;
        $this->ownership_proof = null;
    }
    
    public function restoreOwnershipProof()
    {
        $this->remove_ownership_proof = false;
    }
    
    public function removeAccreditationCertificate()
    {
        $this->remove_accreditation_certificate = true;
        $this->accreditation_certificate = null;
    }
    
    public function restoreAccreditationCertificate()
    {
        $this->remove_accreditation_certificate = false;
    }
    
    public function removeFireSafetyCertificate()
    {
        $this->remove_fire_safety_certificate = true;
        $this->fire_safety_certificate = null;
    }
    
    public function restoreFireSafetyCertificate()
    {
        $this->remove_fire_safety_certificate = false;
    }
    
    public function removeOwnershipProofDoc()
    {
        $this->remove_ownership_proof_doc = true;
        $this->ownership_proof_doc = null;
    }
    
    public function restoreOwnershipProofDoc()
    {
        $this->remove_ownership_proof_doc = false;
    }

    public function save()
    {
        $this->validate();

        DB::beginTransaction();

        try {

            $hospital = Hospital::find(Auth::user()->hospital->id);

            $medicalCompleted = !(
                empty($this->insurance_policy_number) ||
                empty($this->registration_certificate ?? $hospital->registration_certificate) ||
                empty($this->ownership_proof ?? $hospital->ownership_proof) ||
                empty($this->accreditation_certificate ?? $hospital->accreditation_certificate) ||
                empty($this->fire_safety_certificate ?? $hospital->fire_safety_certificate) ||
                empty($this->ownership_proof_doc ?? $hospital->ownership_proof_doc)
            );
    
            $data = [
                'insurance_policy_number' => $this->insurance_policy_number,
                'medical_completed' => $medicalCompleted,
                'medical_status' => 'submitted',
                'updated_at' => now(),
            ];

            $replaceFile = function ($newFile, $oldFile) {
                if ($oldFile && Storage::disk('public')->exists('hospital/documents/' . $oldFile)) {
                    Storage::disk('public')->delete('hospital/documents/' . $oldFile);
                }
    
                $name = Str::uuid() . '.' . $newFile->getClientOriginalExtension();
                $newFile->storeAs('hospital/documents', $name, 'public');
    
                return $name;
            };
    
            if ($this->remove_registration_certificate) {
                Storage::disk('public')->delete('hospital/documents/' . $hospital->registration_certificate);
                $data['registration_certificate'] = null;
            } elseif ($this->registration_certificate) {
                $data['registration_certificate'] = $replaceFile($this->registration_certificate, $hospital->registration_certificate);
            }
    
            if ($this->remove_ownership_proof) {
                Storage::disk('public')->delete('hospital/documents/' . $hospital->ownership_proof);
                $data['ownership_proof'] = null;
            } elseif ($this->ownership_proof) {
                $data['ownership_proof'] = $replaceFile($this->ownership_proof, $hospital->ownership_proof);
            }
    
            if ($this->remove_accreditation_certificate) {
                Storage::disk('public')->delete('hospital/documents/' . $hospital->accreditation_certificate);
                $data['accreditation_certificate'] = null;
            } elseif ($this->accreditation_certificate) {
                $data['accreditation_certificate'] = $replaceFile($this->accreditation_certificate, $hospital->accreditation_certificate);
            }
    
            if ($this->remove_fire_safety_certificate) {
                Storage::disk('public')->delete('hospital/documents/' . $hospital->fire_safety_certificate);
                $data['fire_safety_certificate'] = null;
            } elseif ($this->fire_safety_certificate) {
                $data['fire_safety_certificate'] = $replaceFile($this->fire_safety_certificate, $hospital->fire_safety_certificate);
            }
    
            if ($this->remove_ownership_proof_doc) {
                Storage::disk('public')->delete('hospital/documents/' . $hospital->ownership_proof_doc);
                $data['ownership_proof_doc'] = null;
            } elseif ($this->ownership_proof_doc) {
                $data['ownership_proof_doc'] = $replaceFile($this->ownership_proof_doc, $hospital->ownership_proof_doc);
            }
    
            DB::table('hospitals')->where('id', $hospital->id)->update($data);
            DB::commit();
    
            $this->dispatch('toast', type: 'success', message: 'Medical compliance documents saved successfully!');
            
            return redirect()->route('hospital.hospital-profile.contact-details');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('toast', type: 'error', message: 'Failed to save medical compliance documents: ' . $e->getMessage());
            return;
        }
       
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.medical-compliance');
    }
}