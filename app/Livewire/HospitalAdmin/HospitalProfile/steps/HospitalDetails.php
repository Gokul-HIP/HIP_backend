<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Hospital;
use Illuminate\Support\Facades\Storage;

class HospitalDetails extends Component
{
    use WithFileUploads;

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
        $hospital = Hospital::find(Auth::user()->hospital->id);

        if (!$hospital) abort(403);

        $this->hospital_name = $hospital->hospital_name;
        $this->hospital_subtitle = $hospital->hospital_subtitle;
        $this->hospital_about = $hospital->hospital_about;
        $this->ownership = $hospital->ownership;
        $this->establishment_type = $hospital->establishment_type;
        $this->old_hospital_logo = $hospital->hospital_logo;

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

        $hospital = Hospital::find(Auth::user()->hospital->id);

        $basicCompleted = !(
            empty($this->hospital_name ?? $hospital->hospital_name) ||
            empty($this->hospital_subtitle ?? $hospital->hospital_subtitle) ||
            empty($this->hospital_about ?? $hospital->hospital_about) ||
            empty($this->ownership ?? $hospital->ownership) ||
            empty($this->establishment_type ?? $hospital->establishment_type)
        );
        
        $data = [
            'hospital_name' => $this->hospital_name,
            'hospital_subtitle' => $this->hospital_subtitle,
            'hospital_about' => $this->hospital_about,
            'ownership' => $this->ownership,
            'establishment_type' => $this->establishment_type,
            'basic_details_completed' => $basicCompleted,
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
            Storage::disk('public')->delete('hospital/' . $hospital->hospital_logo);
            $data['hospital_logo'] = null;
        } elseif ($this->hospital_logo) {
            $data['hospital_logo'] = $replaceFile($this->hospital_logo, $hospital->hospital_logo);
        }

        Hospital::where('id', Auth::user()->hospital->id)->update($data);

        $this->dispatch('toast', type: 'success', message: 'Hospital details saved');

        return redirect()->route('hospital.hospital-profile.hospital_location');
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.hospital-details');
    }
}
