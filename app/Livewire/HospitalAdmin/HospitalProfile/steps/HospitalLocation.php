<?php

namespace App\Livewire\HospitalAdmin\HospitalProfile\Steps;

use App\Models\Hospital;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\LocationMaster;
use Illuminate\Support\Facades\DB;

class HospitalLocation extends Component
{
    public $hospital_address;
    public $cities = [];
    public $areasList = [];
    public $area;
    public $city;
    public $pincode;
    public $onboardingStatus = 'draft';
    public $basic_details_completed;
    public $location_completed;
    public $capacity_completed;
    public $medical_completed;
    public $contact_completed;

    public $progressPercentage = 0;

    protected function rules()
    {
        return [
            'hospital_address' => 'required|string|max:500',
            'city' => 'required',
            'area' => 'required',
            'pincode' => 'required|digits:6',
        ];
    }

    public function mount()
    {
        $hospital = Hospital::findOrFail(Auth::user()->hospital->id);

        $this->hospital_address = $hospital->hospital_address;
        $this->pincode = $hospital->pincode;
        $this->onboardingStatus = $hospital->onboarding_status;
        if ($hospital->city) {
            $cityRow = LocationMaster::find($hospital->city);
            $this->city = $cityRow?->city;
        }

        if ($hospital->area) {
            $areaRow = LocationMaster::find($hospital->area);
            $this->area = $areaRow?->area;
        }

        $this->cities = LocationMaster::select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->toArray();

        if ($this->city) {
            $this->areasList = LocationMaster::where('city', $this->city)
                ->select('area')
                ->distinct()
                ->orderBy('area')
                ->pluck('area')
                ->toArray();
        }

        $completed = collect([
            $hospital->basic_details_completed,
            $hospital->location_completed,
            $hospital->capacity_completed,
            $hospital->medical_completed,
            $hospital->contact_completed,
        ])->filter()->count();

        $this->basic_details_completed = $hospital->basic_details_completed;
        $this->location_completed = $hospital->location_completed;
        $this->capacity_completed = $hospital->capacity_completed;
        $this->medical_completed = $hospital->medical_completed;
        $this->contact_completed = $hospital->contact_completed;

        $this->progressPercentage = ($completed / 5) * 100;
    }

    public function updatedCity($city)
    {
        $this->areasList = LocationMaster::where('city', trim($city))
            ->select('area')
            ->distinct()
            ->orderBy('area')
            ->pluck('area')
            ->toArray();

        $this->area = null;
    }

    public function save()
    {
        $this->validate();

        DB::beginTransaction();

        try {

            $city = LocationMaster::where('city', $this->city)->firstOrFail();
            $area = LocationMaster::where('area', $this->area)->firstOrFail();

            $hospital = Hospital::find(Auth::user()->hospital->id);
            
            $locationCompleted = !(
                empty($this->hospital_address ?? $hospital->hospital_address) ||
                empty($this->city ?? $hospital->city) ||
                empty($this->area ?? $hospital->area) ||
                empty($this->pincode ?? $hospital->pincode)
            );

            Hospital::where('id', Auth::user()->hospital->id)->update([
                'hospital_address' => $this->hospital_address,
                'city' => $city->id,
                'area' => $area->id,
                'pincode' => $this->pincode,
                'location_completed' => $locationCompleted,
                'location_status' => 'submitted',
                'updated_at' => now(),
            ]);

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('city', 'Save failed');
            return;
        }

        return redirect()->route('hospital.hospital-profile.hospital_capacity');
    }

    public function render()
    {
        return view('livewire.hospital-admin.hospital-profile.steps.hospital-location');
    }
}
