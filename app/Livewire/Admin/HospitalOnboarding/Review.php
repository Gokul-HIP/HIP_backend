<?php

namespace App\Livewire\Admin\HospitalOnboarding;

use Livewire\Component;
use App\Models\Hospital;
use Illuminate\Support\Facades\Storage;
use App\Models\LocationMaster;
use Illuminate\Support\Facades\Auth;

class Review extends Component
{
    public $hospital_id;
    public $hospital;
    public $hospital_name;
    public $hospital_subtitle;
    public $hospital_about;
    public $ownership;
    public $establishment_type;
    public $hospital_logo;
    public $hospital_logo_url;
    public $selectedDocument = 'hospital_details';
    public $hospital_address;
    public $city;
    public $area;
    public $pincode;
    public $hospital_location_url;
    public $bed_strength;
    public $icu_beds;
    public $operating_theatres;
    public $ambulance_available;
    public $registration_certificate;
    public $ownership_proof;
    public $accreditation_certificate;
    public $fire_safety_certificate;
    public $insurance_policy_number;
    public $ownership_proof_doc;
    public $hospital_admin_name;
    public $hospital_admin_contact;
    public $hospital_admin_email;
    public $hospital_admin_address;
    public $hospital_admin_longitude;
    public $hospital_admin_latitude;
    public $hospital_admin_emergency_contact;
    public $state;
    public $hospital_admin_pincode;
    public $reviewStatus = null;
    public $reviewComments = "";

    public function mount($id)
    {
        $this->hospital_id = $id;

        $this->hospital = Hospital::find($this->hospital_id);

        $this->selectDocument('hospital_details');
        $this->reviewStatus = $this->hospital->onboarding_status;
        $this->reviewComments = $this->hospital->comments;

        // dd($this->hospital_id);
    }

    public function selectDocument($documentKey){
        $this->selectedDocument = $documentKey;

        if($documentKey == 'hospital_details'){
            $hospital = Hospital::find($this->hospital_id);

            $this->hospital_name = $hospital->hospital_name;
            $this->hospital_subtitle = $hospital->hospital_subtitle;
            $this->hospital_about = $hospital->hospital_about;
            $this->ownership = $hospital->ownership;
            $this->establishment_type = $hospital->establishment_type;
            $this->hospital_logo = $hospital->hospital_logo;
            $this->hospital_logo_url = Storage::url('hospital/'.$hospital->hospital_logo);
        }

        if($documentKey == 'hospital_location'){
            $hospital = Hospital::find($this->hospital_id);

            $city = LocationMaster::find($hospital->city);
            $area = LocationMaster::find($hospital->area);

            $this->hospital_address = $hospital->hospital_address;
            $this->city = $city->city;
            $this->area = $area->area;
            $this->pincode = $hospital->pincode;
        }

        if($documentKey == 'hospital_capacity'){
            $hospital = Hospital::find($this->hospital_id);

            $this->bed_strength = $hospital->bed_strength;
            $this->icu_beds = $hospital->icu_beds;
            $this->operating_theatres = $hospital->operating_theatres;
            $this->ambulance_available = $hospital->ambulance_available;
        }

        if($documentKey == 'medical_compliance'){
            $hospital = Hospital::find($this->hospital_id);

            $this->registration_certificate = $hospital->registration_certificate;
            $this->ownership_proof = $hospital->ownership_proof;
            $this->accreditation_certificate = $hospital->accreditation_certificate;
            $this->fire_safety_certificate = $hospital->fire_safety_certificate;
            $this->ownership_proof_doc = $hospital->ownership_proof_doc;
            $this->insurance_policy_number = $hospital->insurance_policy_number;
        }

        if($documentKey == 'contact_details'){
            $hospital = Hospital::find($this->hospital_id);

            $this->hospital_admin_name = $hospital->hospital_admin_name;
            $this->hospital_admin_contact = $hospital->hospital_admin_contact;
            $this->hospital_admin_email = $hospital->hospital_admin_email;
            $this->hospital_admin_emergency_contact = $hospital->hospital_admin_emergency_contact;
            $this->hospital_admin_pincode = $hospital->hospital_admin_pincode;
            $this->state = $hospital->state;
        }

        $this->hospital = Hospital::find($this->hospital_id);

        // $this->dispatch('toast', type: 'success', message: $documentKey.' document selected successfully!');
        
    }

    public function approveDocument($documentKey){

        $this->hospital = Hospital::find($this->hospital_id);

        // dd($documentKey,$this->hospital->id);

        if($documentKey == 'hospital_details'){
            $this->hospital->basic_details_status = 'approved';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Hospital Details approved successfully!');
        }

        if($documentKey == 'hospital_location'){
            $this->hospital->location_status = 'approved';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Hospital Location approved successfully!');
        }

        if($documentKey == 'hospital_capacity'){
            $this->hospital->capacity_status = 'approved';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Hospital Capacity approved successfully!');
        }

        if($documentKey == 'medical_compliance'){
            $this->hospital->medical_status = 'approved';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Medical Compliance approved successfully!');
        }

        if($documentKey == 'contact_details'){
            $this->hospital->contact_status = 'approved';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Contact Details approved successfully!');
        }

        $this->selectDocument($documentKey);

    }

    public function rejectDocument($documentKey){

        $this->hospital = Hospital::find($this->hospital_id);

        // dd($documentKey,$this->hospital->id);

        if($documentKey == 'hospital_details'){
            $this->hospital->basic_details_status = 'rejected';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Hospital Details rejected successfully!');
        }

        if($documentKey == 'hospital_location'){
            $this->hospital->location_status = 'rejected';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Hospital Location rejected successfully!');
        }

        if($documentKey == 'hospital_capacity'){
            $this->hospital->capacity_status = 'rejected';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Hospital Capacity rejected successfully!');
        }

        if($documentKey == 'medical_compliance'){
            $this->hospital->medical_status = 'rejected';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Medical Compliance rejected successfully!');
        }

        if($documentKey == 'contact_details'){
            $this->hospital->contact_status = 'rejected';
            $this->hospital->save();
            $this->dispatch('toast', type: 'success', message: 'Contact Details rejected successfully!');
        }

        $this->selectDocument($documentKey);
    }

    public function submitOnboardingReview(){

        $this->validate([
            'reviewStatus' => 'required|string|in:approved,rejected',
            'reviewComments' => 'nullable|string|max:255',
        ] ,[
            'reviewStatus.required' => 'Please select either Approve or Reject before submitting.',
        ]);

        $hospital = Hospital::find($this->hospital_id);
        
        $hospital->update([
            'onboarding_status' => $this->reviewStatus,
            'comments' => $this->reviewComments ? $this->reviewComments : null,
            'reviewed_at' => now(),
            'reviewed_by' => Auth::user()->id ?? null,
        ]);

        $this->dispatch('toast', type: 'success', message: 'Onboarding review submitted successfully!');

        $this->reviewStatus = null;
        $this->reviewComments = '';

        return redirect()->route('admin.hospital-onboarding.index');

    }

    public function skipOnboarding(){
        return redirect()->route('admin.hospital-onboarding.index');
    }

    public function render()
    {
        return view('livewire.admin.hospital-onboarding.review');
    }
}
