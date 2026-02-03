<?php

namespace App\Livewire\Admin\WellnessServices;

use Livewire\Component;
use App\Models\WellnessCenters;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Form extends Component
{
    use WithFileUploads;

    public $id = null;
    public $isEdit = false;
    public $step = 1;
    public $totalSteps = 5;

    // Step 1: Centre Type
    public $centre_type = '';
    public $operating_mode = '';

    // Step 2: Basic Information
    public $centre_name = '';
    public $age_group_served = '';
    public $description = '';
    public $languages_supported = '';
    public $target_audience = '';

    // Step 3: Location & Address
    public $address_line_1 = '';
    public $address_line_2 = '';
    public $city = '';
    public $state = '';
    public $pincode = '';
    public $latitude = '';
    public $longitude = '';

    // Step 4: Social Media & Contact
    public $centre_website = '';
    public $centre_instagram_links = '';
    public $centre_facebook_links = '';
    public $centre_linkedin_links = '';
    public $centre_twitter_links = '';
    public $centre_youtube_links = '';
    public $contact_person_name = '';
    public $contact_person_mobile = '';
    public $contact_person_email = '';

    // Step 5: Legal Compliance
    public $business_registration_type = '';
    public $gst_number = '';
    public $insurance_coverage = [];
    public $registration_certificate;
    public $ownership_proof;
    public $accreditation_certificate;
    public $fire_safety_certificate;

    // Existing file paths (for edit mode)
    public $existing_registration_certificate = '';
    public $existing_ownership_proof = '';
    public $existing_accreditation_certificate = '';
    public $existing_fire_safety_certificate = '';

    public function mount($id = null)
    {
        if ($id) {
            $this->isEdit = true;
            $this->id = $id;
            $this->loadWellnessCenter($id);
        }
    }

    public function loadWellnessCenter($id)
    {
        $wellnessCenter = WellnessCenters::findOrFail($id);

        // Step 1
        $this->centre_type = $wellnessCenter->centre_type ?? '';
        $this->operating_mode = $wellnessCenter->operating_mode ?? '';

        // Step 2
        $this->centre_name = $wellnessCenter->centre_name ?? '';
        $this->age_group_served = $wellnessCenter->age_group_served ?? '';
        $this->description = $wellnessCenter->description ?? '';
        $this->languages_supported = $wellnessCenter->languages_supported ?? '';
        $this->target_audience = $wellnessCenter->target_audience ?? '';

        // Step 3
        $this->address_line_1 = $wellnessCenter->address_line_1 ?? '';
        $this->address_line_2 = $wellnessCenter->address_line_2 ?? '';
        $this->city = $wellnessCenter->city ?? '';
        $this->state = $wellnessCenter->state ?? '';
        $this->pincode = $wellnessCenter->pincode ?? '';
        $this->latitude = $wellnessCenter->latitude ?? '';
        $this->longitude = $wellnessCenter->longitude ?? '';

        // Step 4
        $this->centre_website = $wellnessCenter->centre_website ?? '';
        $this->centre_instagram_links = $wellnessCenter->centre_instagram_links ?? '';
        $this->centre_facebook_links = $wellnessCenter->centre_facebook_links ?? '';
        $this->centre_linkedin_links = $wellnessCenter->centre_linkedin_links ?? '';
        $this->centre_twitter_links = $wellnessCenter->centre_twitter_links ?? '';
        $this->centre_youtube_links = $wellnessCenter->centre_youtube_links ?? '';
        $this->contact_person_name = $wellnessCenter->contact_person_name ?? '';
        $this->contact_person_mobile = $wellnessCenter->contact_person_mobile ?? '';
        $this->contact_person_email = $wellnessCenter->contact_person_email ?? '';

        // Step 5
        $this->business_registration_type = $wellnessCenter->business_registration_type ?? '';
        $this->gst_number = $wellnessCenter->gst_number ?? '';
        // Handle insurance_coverage as array (JSON from database)
        $insuranceCoverage = $wellnessCenter->insurance_coverage ?? [];
        $this->insurance_coverage = is_array($insuranceCoverage) ? $insuranceCoverage : (is_string($insuranceCoverage) && !empty($insuranceCoverage) ? json_decode($insuranceCoverage, true) ?? [] : []);
        
        // Existing files
        $this->existing_registration_certificate = $wellnessCenter->registration_certificate ?? '';
        $this->existing_ownership_proof = $wellnessCenter->ownership_proof ?? '';
        $this->existing_accreditation_certificate = $wellnessCenter->accreditation_certificate ?? '';
        $this->existing_fire_safety_certificate = $wellnessCenter->fire_safety_certificate ?? '';
    }

    public function next()
    {
        $this->validateStep();
        if ($this->step < $this->totalSteps) {
            $this->step++;
        }
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function validateStep()
    {
        switch ($this->step) {
            case 1:
                $this->validate([
                    'centre_type' => 'required',
                    'operating_mode' => 'required',
                ], [
                    'centre_type.required' => 'Please select a centre type',
                    'operating_mode.required' => 'Please select an operating mode',
                ]);
                break;
            case 2:
                $this->validate([
                    'centre_name' => 'required|string|max:255',
                    'description' => 'required|string',
                ], [
                    'centre_name.required' => 'Centre name is required',
                    'description.required' => 'Description is required',
                ]);
                break;
            case 3:
                $this->validate([
                    'address_line_1' => 'required|string',
                    'address_line_2' => 'required|string',
                    'city' => 'required|string',
                    'state' => 'required|string',
                    'pincode' => 'required|string|max:10',
                    'latitude' => 'required|numeric',
                    'longitude' => 'required|numeric',
                ]);
                break;
            case 4:
                $validationRules = [
                    'centre_website' => 'required|url',
                    'centre_instagram_links' => 'required|string',
                    'centre_facebook_links' => 'required|string',
                    'centre_linkedin_links' => 'required|string',
                    'centre_twitter_links' => 'required|string',
                    'centre_youtube_links' => 'required|string',
                    'contact_person_name' => 'required|string',
                    'contact_person_mobile' => 'required|string|max:15',
                    'contact_person_email' => 'required|email',
                ];

                if ($this->isEdit) {
                    $validationRules['contact_person_mobile'] = 'required|string|max:15|unique:wellness_centres,contact_person_mobile,' . $this->id;
                    $validationRules['contact_person_email'] = 'required|email|unique:wellness_centres,contact_person_email,' . $this->id;
                } else {
                    $validationRules['contact_person_mobile'] = 'required|string|max:15|unique:wellness_centres,contact_person_mobile';
                    $validationRules['contact_person_email'] = 'required|email|unique:wellness_centres,contact_person_email';
                }

                $this->validate($validationRules);
                break;
            case 5:
                if ($this->isEdit) {
                    $this->validate([
                        'registration_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'ownership_proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'accreditation_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'fire_safety_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'insurance_coverage' => 'nullable|array',
                        'insurance_coverage.*' => 'string',
                    ]);
                } else {
                    $this->validate([
                        'registration_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'ownership_proof' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'accreditation_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'fire_safety_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
                        'insurance_coverage' => 'nullable|array',
                        'insurance_coverage.*' => 'string',
                    ], [
                        'registration_certificate.required' => 'Registration certificate is required',
                        'ownership_proof.required' => 'Ownership proof is required',
                        'accreditation_certificate.required' => 'Accreditation certificate is required',
                        'fire_safety_certificate.required' => 'Fire safety certificate is required',
                    ]);
                }
                break;
        }
    }

    public function save()
    {
        $this->validateStep();

        try {
            $data = [
                'centre_type' => $this->centre_type,
                'operating_mode' => $this->operating_mode,
                'centre_name' => $this->centre_name,
                'age_group_served' => $this->age_group_served,
                'description' => $this->description,
                'languages_supported' => $this->languages_supported,
                'target_audience' => $this->target_audience,
                'address_line_1' => $this->address_line_1,
                'address_line_2' => $this->address_line_2,
                'city' => $this->city,
                'state' => $this->state,
                'pincode' => $this->pincode,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'centre_website' => $this->centre_website,
                'centre_instagram_links' => $this->centre_instagram_links,
                'centre_facebook_links' => $this->centre_facebook_links,
                'centre_linkedin_links' => $this->centre_linkedin_links,
                'centre_twitter_links' => $this->centre_twitter_links,
                'centre_youtube_links' => $this->centre_youtube_links,
                'contact_person_name' => $this->contact_person_name,
                'contact_person_mobile' => $this->contact_person_mobile,
                'contact_person_email' => $this->contact_person_email,
                'business_registration_type' => $this->business_registration_type,
                'gst_number' => $this->gst_number,
                'insurance_coverage' => !empty($this->insurance_coverage) ? $this->insurance_coverage : null,
            ];

            if (!$this->isEdit) {
                $data['status'] = 'pending';
            }

            // Handle file uploads
            $fileFields = [
                'registration_certificate',
                'ownership_proof',
                'accreditation_certificate',
                'fire_safety_certificate',
            ];

            foreach ($fileFields as $field) {
                if ($this->$field) {
                    // Delete old file if exists (edit mode)
                    if ($this->isEdit) {
                        $existingField = 'existing_' . $field;
                        if ($this->$existingField && Storage::disk('public')->exists('wellness-centers/documents/' . $this->$existingField)) {
                            Storage::disk('public')->delete('wellness-centers/documents/' . $this->$existingField);
                        }
                    }

                    // Upload new file
                    $extension = $this->$field->getClientOriginalExtension();
                    $fileName = Str::uuid() . '.' . $extension;
                    $this->$field->storeAs('wellness-centers/documents', $fileName, 'public');
                    $data[$field] = $fileName;
                } elseif ($this->isEdit) {
                    // Keep existing file if no new file uploaded (edit mode)
                    $existingField = 'existing_' . $field;
                    if ($this->$existingField) {
                        $data[$field] = $this->$existingField;
                    }
                }
            }

            if ($this->isEdit) {
                $wellnessCenter = WellnessCenters::findOrFail($this->id);
                $wellnessCenter->update($data);
                $message = 'Wellness centre updated successfully!';
            } else {
                WellnessCenters::create($data);
                $message = 'Wellness centre added successfully!';
            }

            session()->flash('success', $message);
            return redirect()->route('admin.wellness-services.index');
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Failed to save wellness centre: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.wellness-services.form');
    }
}

