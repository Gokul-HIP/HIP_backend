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
    // public bool $selectAll = false;
    public array $toRemove = [];
    // public bool $selectAllSpecialities = false;
    protected $specialitieService;
    public bool $selectAllToRemove = false;

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
            // 'selectAllSpecialities',
            'selectAllToRemove',
            'toRemove',
        ]);
        $this->resetErrorBag();
        $this->step = 1;
    }

    public function updatedSpecialitySearch()
    {
        $this->resetPage();
    }

    // public function updatedSelectAll($value)
    // {
    //     if ($value) {
    //         $specialities = SpecialitiesMaster::query()
    //             ->when($this->specialitySearch, function ($q) {
    //                 $q->where(function ($sub) {
    //                     $sub->where('name', 'like', '%' . $this->specialitySearch . '%')
    //                         ->orWhere('description', 'like', '%' . $this->specialitySearch . '%');
    //                 });
    //             })
    //             ->get();

    //         $this->selectedSpecialities = $specialities->pluck('id')->toArray();
    //     } else {
    //         $this->selectedSpecialities = [];
    //     }
    // }
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

//    public function updatedSelectAllSpecialities($value)
//     {
//         if ($value) {
//             $this->selectedSpecialities = $this->specialities->pluck('id')->toArray();
//         } else {
//             $this->selectedSpecialities = [];
//         }
//     }

    public function toggleSelectAllSpecialities()
    {
        $visibleIds = $this->specialities->pluck('id')->toArray();

        $allSelectedOnPage =
            count($visibleIds) > 0 &&
            count(array_diff($visibleIds, $this->selectedSpecialities)) === 0;

        if ($allSelectedOnPage) {
            // Unselect only current page
            $this->selectedSpecialities = array_values(
                array_diff($this->selectedSpecialities, $visibleIds)
            );
        } else {
            // Select only current page
            $this->selectedSpecialities = array_unique(
                array_merge($this->selectedSpecialities, $visibleIds)
            );
        }
    }

    public function getIsAllSpecialitiesSelectedOnPageProperty()
    {
        $visibleIds = $this->specialities->pluck('id')->toArray();

        if (empty($visibleIds)) {
            return false;
        }

        return count(array_diff($visibleIds, $this->selectedSpecialities)) === 0;
    }

    public function updatedSelectedSpecialities()
    {
        $visibleIds = $this->specialities->pluck('id')->toArray();

        // $this->selectAllSpecialities =
        //     count($this->selectedSpecialities) > 0 &&
        //     count(array_diff($visibleIds, $this->selectedSpecialities)) === 0;
    }

    public function toggleSpeciality($specialityId)
    {
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
        // $this->selectAllSpecialities = false;

        if (empty($this->selectedSpecialities)) {
            $this->step = 1;
        }
    }

    // public function updatedToRemove()
    // {
    //     $this->selectAll =
    //         count($this->toRemove) > 0 &&
    //         count($this->toRemove) === count($this->selectedSpecialities);
    // }

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
        }

        if ($this->step < 2) {
            $this->step++;
            $this->toRemove = [];
        }
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->toRemove = [];
        }
    }

    public function save()
    {
        $this->validate([
            'selectedSpecialities' => 'required|array|min:1',
        ]);

        try {
            $specialityMasters = SpecialitiesMaster::whereIn('id', $this->selectedSpecialities)->get();
            
            $createdCount = 0;
            
            foreach ($specialityMasters as $master) {
                $latestId = Speciality::latest('id')->value('id') ?? 0;
                $hospital = Hospital::find($this->hospitalId);
                
                $specialityCode = $hospital->hospital_name . '-' . 'SPECIALITY' . '-' . date('Y') . '-' . str_pad($latestId + 1 + $createdCount, 4, '0', STR_PAD_LEFT);

                // Handle image if exists
                $imageName = null;
                if ($master->display_image) {
                    // Copy image from master or use default
                    $imageName = $master->display_image;
                }

                $specialityData = [
                    'speciality_name' => $master->name,
                    'speciality_code' => $specialityCode,
                    'speciality_description' => $master->description ?? '',
                    'speciality_logo' => $imageName ?? 'default-speciality.png',
                    'department_category' => $master->name, 
                    'status' => 'inactive',
                    'hospital_id' => $this->hospitalId,
                    'organization_id' => $this->organizationId,
                ];

                $this->specialitieService->createSpeciality($specialityData);
                $createdCount++;
            }

            Flux::modal('bulk-add-specialitie')->close();
            $this->dispatch('relodSpe');
            $this->resetInput();
            
            $this->dispatch(
                'toast',
                type: 'success',
                message: "{$createdCount} specialities added successfully!"
            );
            
        } catch (\Exception $e) {
            $this->addError('save', 'Failed to save specialities: ' . $e->getMessage());
            Log::error('Failed to save specialities: ' . $e->getMessage());
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

