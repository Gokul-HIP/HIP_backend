<?php

namespace App\Livewire\Admin\Organization\Hospital\Specialitie;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Models\Hospital;
use App\Models\SpecialitiesMaster;
use App\Services\SpecialitieService;
use App\Models\Speciality;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class BulkAddSpecialitie extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    
    public int $step = 1;
    public ?int $hospitalId = null;
    public ?int $organizationId = null;
    public string $specialitySearch = '';
    public array $selectedSpecialities = [];
    public array $toRemove = [];
    protected $specialitieService;
    public bool $selectAllToRemove = false;
    public int $modalKey = 0;

    public function boot(SpecialitieService $specialitieService)
    {
        $this->specialitieService = $specialitieService;
    }

    #[On('open-bulk-add-specialitie')]
    public function open(int $hospitalId)
    {
        $this->resetInput();
        $this->resetPage();

        $this->hospitalId = $hospitalId;

        $hospital = Hospital::find($hospitalId);
        if ($hospital) {
            $this->organizationId = $hospital->organization_id;
        }

        Flux::modal('bulk-add-specialitie')->show();
    }

    public function resetInput()
    {
        $this->reset([
            'step',
            'specialitySearch',
            'selectedSpecialities',
            'selectAllToRemove',
            'toRemove',
        ]);
        $this->resetErrorBag();
        $this->resetPage();
        $this->step = 1;
        $this->modalKey++;
    }

    public function updatedSpecialitySearch()
    {
        $this->resetPage();
    }

    public function getSpecialitiesProperty()
    {
        return SpecialitiesMaster::query()
            ->when($this->specialitySearch, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->specialitySearch . '%')
                        ->orWhere('description', 'like', '%' . $this->specialitySearch . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function updatedToRemove()
    {
        $this->selectAllToRemove =
            count($this->toRemove) > 0 &&
            count($this->toRemove) === count($this->selectedSpecialities);
    }

    public function updatedSelectAllToRemove($value)
    {
        if ($this->step !== 2) {
            return;
        }

        $this->toRemove = $value
            ? $this->selectedSpecialities
            : [];
    }

    public function toggleSelectAllSpecialities()
    {
        $visibleIds = $this->specialities->pluck('id')->toArray();
        
        // Filter out already added specialities
        $availableIds = array_filter($visibleIds, function($specialityId) {
            return !$this->isSpecialityAlreadyAdded($specialityId);
        });

        $allAvailableSelectedOnPage =
            count($availableIds) > 0 &&
            count(array_diff($availableIds, $this->selectedSpecialities)) === 0;

        if ($allAvailableSelectedOnPage) {
            // Unselect only current page (available specialities)
            $this->selectedSpecialities = array_values(
                array_diff($this->selectedSpecialities, $availableIds)
            );
        } else {
            // Select only available specialities on current page (exclude already added)
            $this->selectedSpecialities = array_unique(
                array_merge($this->selectedSpecialities, $availableIds)
            );
        }
    }

    public function getIsAllSpecialitiesSelectedOnPageProperty()
    {
        $visibleIds = $this->specialities->pluck('id')->toArray();
        
        // Filter out already added specialities
        $availableIds = array_filter($visibleIds, function($specialityId) {
            return !$this->isSpecialityAlreadyAdded($specialityId);
        });

        if (empty($availableIds)) {
            return false;
        }

        return count(array_diff($availableIds, $this->selectedSpecialities)) === 0;
    }

    public function updatedSelectedSpecialities()
    {
        // This method can be kept for future hooks if needed
    }

    public function toggleSpeciality($specialityId)
    {
        // FIXED: Prevent toggling if already added
        if ($this->isSpecialityAlreadyAdded($specialityId)) {
            return;
        }
        
        if (in_array($specialityId, $this->selectedSpecialities)) {
            $this->selectedSpecialities = array_diff($this->selectedSpecialities, [$specialityId]);
        } else {
            $this->selectedSpecialities[] = $specialityId;
        }
        
        $this->selectedSpecialities = array_values($this->selectedSpecialities);
    }

    public function toggleForRemoval($specialityId)
    {
        if (in_array($specialityId, $this->toRemove)) {
            $this->toRemove = array_diff($this->toRemove, [$specialityId]);
        } else {
            $this->toRemove[] = $specialityId;
        }
        
        $this->toRemove = array_values($this->toRemove);
    }

    public function deleteSelected()
    {
        if (empty($this->toRemove)) {
            return;
        }

        $this->selectedSpecialities = array_values(
            array_diff($this->selectedSpecialities, $this->toRemove)
        );

        $this->toRemove = [];
        $this->selectAllToRemove = false;

        if (empty($this->selectedSpecialities)) {
            $this->step = 1;
        }
    }

    public function backToAddMore()
    {
        $this->step = 1;
        $this->toRemove = [];
    }

    public function next()
    {
        if ($this->step === 1) {
            $this->validate([
                'selectedSpecialities' => 'required|array|min:1',
            ], [
                'selectedSpecialities.required' => 'Please select at least one speciality',
                'selectedSpecialities.min' => 'Please select at least one speciality',
            ]);

            // Check for already existing specialities
            $existingSpecialities = $this->checkExistingSpecialities();
            
            if (!empty($existingSpecialities)) {
                $specialityNames = implode(', ', array_column($existingSpecialities, 'name'));
                
                $this->dispatch('toast',
                    type: 'error',
                    message: "The following specialities already exist: {$specialityNames}. Please remove them before proceeding."
                );
                
                return;
            }
        }

        if ($this->step < 2) {
            $this->step++;
            $this->toRemove = [];
        }
    }

    public function checkExistingSpecialities()
    {
        $existingSpecialities = [];
        
        $specialityMasters = SpecialitiesMaster::whereIn('id', $this->selectedSpecialities)->get();
        
        foreach ($specialityMasters as $master) {
            // Check by speciality_master_id
            $existsByMasterId = Speciality::where('hospital_id', $this->hospitalId)
                ->where('speciality_master_id', $master->id)
                ->exists();
            
            // Also check by name to catch older records
            $existsByName = Speciality::where('hospital_id', $this->hospitalId)
                ->where('speciality_name', $master->name)
                ->exists();
            
            if ($existsByMasterId || $existsByName) {
                $existingSpecialities[] = [
                    'id' => $master->id,
                    'name' => $master->name
                ];
            }
        }
        
        return $existingSpecialities;
    }

    public function isSpecialityAlreadyAdded($specialityMasterId)
    {
        if (!$this->hospitalId) {
            return false;
        }

        // Get the master speciality
        $master = SpecialitiesMaster::find($specialityMasterId);
        
        if (!$master) {
            return false;
        }

        // Check by speciality_master_id first (preferred method)
        $existsByMasterId = Speciality::where('hospital_id', $this->hospitalId)
            ->where('speciality_master_id', $specialityMasterId)
            ->exists();

        if ($existsByMasterId) {
            return true;
        }

        // Also check by name to catch specialities added before master_id was implemented
        $existsByName = Speciality::where('hospital_id', $this->hospitalId)
            ->where('speciality_name', $master->name)
            ->exists();

        return $existsByName;
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->toRemove = [];
        }
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('bulk-add-specialitie')->close();
    }

    public function generateSpecialityCode($hospital)
    {
        do {
            $number = rand(1000, 9999);
            $code = Str::slug($hospital->name, '-')
                . '-SPECIALITY-' . date('Y') . '-' . $number;
        } while (Speciality::where('speciality_code', $code)->exists());

        return strtoupper($code);
    }

    public function save()
    {
        $this->validate([
            'selectedSpecialities' => 'required|array|min:1',
        ]);

        try {
            $specialityMasters = SpecialitiesMaster::whereIn('id', $this->selectedSpecialities)->get();
            $hospital = Hospital::find($this->hospitalId);
            
            if (!$hospital) {
                throw new \Exception('Hospital not found');
            }
            
            $createdCount = 0;
            $skippedCount = 0;
            
            foreach ($specialityMasters as $master) {
                // Double-check before creating to prevent duplicates
                $existsByMasterId = Speciality::where('hospital_id', $this->hospitalId)
                    ->where('speciality_master_id', $master->id)
                    ->exists();
                
                $existsByName = Speciality::where('hospital_id', $this->hospitalId)
                    ->where('speciality_name', $master->name)
                    ->exists();
                
                if ($existsByMasterId || $existsByName) {
                    $skippedCount++;
                    continue; // Skip this speciality as it already exists
                }
                
                $specialityCode = $this->generateSpecialityCode($hospital);

                $imageName = null;
                if ($master->display_image) {
                    $imageName = basename($master->display_image);
                }

                $specialityData = [
                    'speciality_name' => $master->name,
                    'speciality_code' => $specialityCode,
                    'speciality_description' => $master->description ?? '',
                    'speciality_logo' => $imageName,
                    'department_category' => $master->name,
                    'status' => 'inactive',
                    'hospital_id' => $this->hospitalId,
                    'organization_id' => $this->organizationId,
                    'speciality_master_id' => $master->id, // Make sure to save the master_id
                ];

                try {
                    $this->specialitieService->createSpeciality($specialityData);
                    $createdCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($e->errorInfo[1] == 1062) {
                        $specialityData['speciality_code'] = $this->generateSpecialityCode($hospital);
                        $this->specialitieService->createSpeciality($specialityData);
                        $createdCount++;
                    } else {
                        throw $e;
                    }
                }
            }

            Flux::modal('bulk-add-specialitie')->close();
            $this->dispatch('relodSpe');
            $this->resetInput();
            
            $message = "{$createdCount} specialities added successfully!";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} already existed and were skipped)";
            }
            
            $this->dispatch(
                'toast',
                type: 'success',
                message: $message
            );
            
        } catch (\Exception $e) {
            $this->addError('save', 'Failed to save specialities: ' . $e->getMessage());
            Log::error('Failed to save specialities: ' . $e->getMessage());
            
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Failed to save specialities: ' . $e->getMessage()
            );
        }
    }

    public function getSelectedSpecialitiesDetails()
    {
        if (empty($this->selectedSpecialities)) {
            return collect();
        }

        return SpecialitiesMaster::whereIn('id', $this->selectedSpecialities)->get();
    }

    public function render()
    {
        $specialities = SpecialitiesMaster::query()
            ->when($this->specialitySearch, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->specialitySearch . '%')
                        ->orWhere('description', 'like', '%' . $this->specialitySearch . '%');
                });
            })
            ->orderBy('name')
            ->paginate(10);

        $selectedSpecialitiesDetails = $this->getSelectedSpecialitiesDetails();

        return view(
            'livewire.admin.organization.hospital.specialitie.bulk-add-specialitie',
            compact('specialities', 'selectedSpecialitiesDetails')
        );
    }
}