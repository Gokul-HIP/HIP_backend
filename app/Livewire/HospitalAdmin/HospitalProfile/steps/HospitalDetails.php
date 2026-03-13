<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Hospital;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class HospitalDetails extends Component
{
    use WithFileUploads;

    public ?int $hospitalId = null;
    public $hospital_name;
    public $hospital_subtitle;
    public $hospital_about;
    public $ownership;
    public $establishment_type;
    public $basic_details_completed;
    public $location_completed;
    public $capacity_completed;
    public $medical_completed;
    public $contact_completed;
    public $progressPercentage = 0;
    public $hospital_logo;
    public $old_hospital_logo;
    public bool $remove_image = false;
    public $onboardingStatus = 'draft';
    public $currentStep = 1;
    public $totalSteps = 5;

    protected function rules()
    {
        return [
            'hospital_name' => 'nullable|string|max:255',
            'hospital_subtitle' => 'required|string|max:255',
            'hospital_about' => 'required|string',
            'ownership' => 'nullable|string|max:255',
            'establishment_type' => 'nullable|string|max:255',
            'hospital_logo' => 'nullable|image|max:2048',
        ];
    }

    protected $messages = [
        'hospital_subtitle.required' => 'Sub Title is required.',
        'hospital_about.required' => 'About Hospital is required.',
        'ownership.nullable' => 'Nature of Ownership is required.',
        'establishment_type.nullable' => 'Establishment Type is required.',
        'hospital_logo.image' => 'The file must be an image.',
        'hospital_logo.max' => 'The image must not exceed 2MB.',
    ];

    public function mount()
    {
        // $hospitalId can be passed from the Blade view; Livewire will persist it between requests
        $hospitalId = $this->hospitalId ?? request()->get('hospital_id');

        if (! $hospitalId && Auth::user()->hospital) {
            $hospitalId = Auth::user()->hospital->id;
        }

        $this->hospitalId = $hospitalId ?: null;

        $hospital = $hospitalId ? Hospital::find($hospitalId) : null;

        if (! $hospital) {
            // If no hospital context, send user back to hospitals list instead of 403
            return redirect()->route('healthcare.hospitals.index');
        }

        $this->hospital_name = $hospital->name;
        $this->hospital_subtitle = $hospital->subtitle;
        $this->hospital_about = $hospital->about;
        $this->ownership = $hospital->ownership;
        $this->establishment_type = $hospital->establishment_type;
        $this->old_hospital_logo = $hospital->logo;

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

    public function updatedHospitalLogo()
    {
        $this->validateOnly('hospital_logo');
    }

    public function removeImage()
    {
        $this->remove_image = true;
        $this->hospital_logo = null;
    }

    public function restoreImage()
    {
        $this->remove_image = false;
    }

    public function save()
    {
        $this->validate();

        $hospitalId = $this->hospitalId;

        $hospital = $hospitalId ? Hospital::find($hospitalId) : null;

        if (! $hospital) {
            // Log::warning('HospitalDetails save: hospital not found', [
            //     'hospital_id_param' => request()->get('hospital_id'),
            //     'resolved_hospital_id' => $hospitalId,
            //     'user_id' => Auth::id(),
            // ]);
            return redirect()->route('healthcare.hospitals.index');
        }

        $basicCompleted = !(
            empty($this->hospital_name ?? $hospital->name) ||
            empty($this->hospital_subtitle ?? $hospital->subtitle) ||
            empty($this->hospital_about ?? $hospital->about) ||
            empty($this->ownership ?? $hospital->ownership) ||
            empty($this->establishment_type ?? $hospital->establishment_type)
        );

        $this->onboardingStatus = $hospital->onboarding_status;
        
        $data = [
            'name' => $this->hospital_name,
            'subtitle' => $this->hospital_subtitle,
            'about' => $this->hospital_about,
            'ownership' => $this->ownership,
            'establishment_type' => $this->establishment_type,
            'basic_details_completed' => $basicCompleted,
            'basic_details_status' => 'submitted',
        ];  

        $replaceFile = function ($newFile, $oldFile) {
            if ($oldFile && Storage::disk('public')->exists('hospital/' . $oldFile)) {
                Storage::disk('public')->delete('hospital/' . $oldFile);
            }
            $name = Str::uuid() . '.' . $newFile->getClientOriginalExtension();
            $newFile->storeAs('hospital', $name, 'public');
            return $name;
        };


        if ($this->remove_image) {
            Storage::disk('public')->delete('hospital/' . $hospital->logo);
            $data['logo'] = null;
        } elseif ($this->hospital_logo) {
            $data['logo'] = $replaceFile($this->hospital_logo, $hospital->logo);
        }

        Hospital::where('id', $hospital->id)->update($data);

        // Log::info('HospitalDetails save: success, redirecting to location step', [
        //     'hospital_id' => $hospital->id,
        //     'user_id' => Auth::id(),
        // ]);

        $this->dispatch('toast', type: 'success', message: 'Hospital details saved');

        return redirect()->route(
            'healthcare.hospital-profile.hospital_location',
            ['hospital_id' => $hospital->id]
        );
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.hospital-details');
    }
}
