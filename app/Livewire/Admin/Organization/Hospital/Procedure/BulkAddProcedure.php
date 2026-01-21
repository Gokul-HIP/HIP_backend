<?php

namespace App\Livewire\Admin\Organization\Hospital\Procedure;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Models\Hospital;
use App\Models\ProcedureMaster;
use App\Services\ProcedureService;
use App\Models\Procedure;
use App\Models\Speciality;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAddProcedure extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    
    public int $step = 1;
    public ?int $hospitalId = null;
    public ?int $organizationId = null;
    public string $procedureSearch = '';
    public array $selectedProcedures = [];
    // public bool $selectAll = false;
    // public bool $selectAllProcedures = false;
    public array $toRemove = [];
    public bool $selectAllToRemove = false;

    protected $procedureService;

    public function boot(ProcedureService $procedureService)
    {
        $this->procedureService = $procedureService;
    }

    #[On('open-bulk-add-procedure')]
    public function open(int $hospitalId)
    {
        $this->resetInput();
        $this->resetPage();

        $this->hospitalId = $hospitalId;

        $hospital = Hospital::find($hospitalId);
        if ($hospital) {
            $this->organizationId = $hospital->organization_id;
        }

        Flux::modal('bulk-add-procedure')->show();
    }

    public function getProceduresProperty()
    {
        return ProcedureMaster::query()
            ->when($this->procedureSearch, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->procedureSearch . '%')
                        ->orWhere('description', 'like', '%' . $this->procedureSearch . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    // public function updatedSelectAllProcedures($value)
    // {
    //     if ($value) {
    //         $this->selectedProcedures = $this->procedures->pluck('id')->toArray();
    //     } else {
    //         $this->selectedProcedures = [];
    //     }
    // }

    public function toggleSelectAllProcedures()
    {
        $visibleIds = $this->procedures->pluck('id')->toArray();

        $allSelectedOnPage =
            count($visibleIds) > 0 &&
            count(array_diff($visibleIds, $this->selectedProcedures)) === 0;

        if ($allSelectedOnPage) {
            // Unselect only current page
            $this->selectedProcedures = array_values(
                array_diff($this->selectedProcedures, $visibleIds)
            );
        } else {
            // Select only current page
            $this->selectedProcedures = array_unique(
                array_merge($this->selectedProcedures, $visibleIds)
            );
        }
    }

    public function getIsAllProceduresSelectedOnPageProperty()
    {
        $visibleIds = $this->procedures->pluck('id')->toArray();

        if (empty($visibleIds)) {
            return false;
        }

        return count(array_diff($visibleIds, $this->selectedProcedures)) === 0;
    }

    public function updatedSelectAllToRemove($value)
    {
        if ($this->step !== 2) {
            return;
        }

        $this->toRemove = $value ? $this->selectedProcedures : [];
    }

    #[On('bulk-procedure-closed')]
    public function resetOnClose()
    {
        $this->resetInput();
    }

    public function resetInput()
    {
        $this->reset([
            'step',
            'procedureSearch',
            'selectedProcedures',
            // 'selectAllProcedures',
            'selectAllToRemove',
            'toRemove',
        ]);
        $this->resetErrorBag();
        $this->step = 1;
    }

    public function updatedProcedureSearch()
    {
        $this->resetPage();
    }

    // public function updatedSelectAll($value)
    // {
    //     if ($value) {
    //         $procedures = ProcedureMaster::query()
    //             ->when($this->procedureSearch, function ($q) {
    //                 $q->where(function ($sub) {
    //                     $sub->where('name', 'like', '%' . $this->procedureSearch . '%')
    //                         ->orWhere('description', 'like', '%' . $this->procedureSearch . '%');
    //                 });
    //             })
    //             ->get();

    //         $this->selectedProcedures = $procedures->pluck('id')->toArray();
    //     } else {
    //         $this->selectedProcedures = [];
    //     }
    // }

    // public function updatedSelectAllToRemove($value)
    // {
    //     if ($this->step !== 2) {
    //         return;
    //     }

    //     if ($value) {
    //         $this->toRemove = $this->selectedProcedures;
    //     } else {
    //         $this->toRemove = [];
    //     }
    // }

    public function toggleProcedure($procedureId)
    {
        if (in_array($procedureId, $this->selectedProcedures)) {
            $this->selectedProcedures = array_diff($this->selectedProcedures, [$procedureId]);
        } else {
            $this->selectedProcedures[] = $procedureId;
        }
        
        $this->selectedProcedures = array_values($this->selectedProcedures);
    }

    // public function toggleForRemoval($procedureId)
    // {
    //     if (in_array($procedureId, $this->toRemove)) {
    //         $this->toRemove = array_diff($this->toRemove, [$procedureId]);
    //     } else {
    //         $this->toRemove[] = $procedureId;
    //     }
        
    //     // $this->toRemove = array_values($this->toRemove);
    // }

    public function deleteSelected()
    {
        if (empty($this->toRemove)) {
            return;
        }
    
        $this->selectedProcedures = array_values(
            array_diff($this->selectedProcedures, $this->toRemove)
        );
    
        $this->toRemove = [];
        $this->selectAllToRemove = false;
    
        if (empty($this->selectedProcedures)) {
            // $this->selectAllProcedures = false;
            $this->step = 1;
        }
    }

    public function updatedSelectedProcedures()
    {
        $visibleIds = $this->procedures->pluck('id')->toArray();

    //     $this->selectAllProcedures =
    //         count($this->selectedProcedures) > 0 &&
    //         count(array_diff($visibleIds, $this->selectedProcedures)) === 0;
    }

    public function updatedToRemove()
    {
        $this->selectAllToRemove =
            count($this->toRemove) > 0 &&
            count($this->toRemove) === count($this->selectedProcedures);
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
                'selectedProcedures' => 'required|array|min:1',
            ], [
                'selectedProcedures.required' => 'Please select at least one procedure',
                'selectedProcedures.min' => 'Please select at least one procedure',
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
            'selectedProcedures' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();
            
            $procedureMasters = ProcedureMaster::whereIn('id', $this->selectedProcedures)->get();
            $hospital = Hospital::find($this->hospitalId);
            
            if (!$hospital) {
                throw new \Exception('Hospital not found');
            }
            
            $createdCount = 0;
            $latestId = Procedure::latest('id')->value('id') ?? 0;
            
            foreach ($procedureMasters as $master) {
                $specialityId = null;
                if ($master->speciality_master_id) {
                    $speciality = Speciality::firstOrCreate(
                        [
                            'speciality_master_id' => $master->speciality_master_id,
                            'hospital_id' => $this->hospitalId,
                        ],
                        [
                            'speciality_name'        => $master->specialityMaster->name,
                            'speciality_code'        => $hospital->hospital_name . '-' . 'SPECIALITY' . '-' . date('Y') . '-' . str_pad($latestId + 1 + $createdCount, 4, '0', STR_PAD_LEFT),
                            'department_category'    => $master->specialityMaster->name,
                            'status'                 => 'inactive',
                            'organization_id'        => $this->organizationId,
                            'speciality_description' => $master->specialityMaster->description ?? '',
                            'speciality_logo'        => 'default-speciality.png',
                        ]
                    );
                
                    $specialityId = $speciality->id;
                }

                $procedureCode = $hospital->hospital_name . '-PROC-' . date('Y') . '-' . str_pad($latestId + 1 + $createdCount, 4, '0', STR_PAD_LEFT);

                $procedureData = [
                    'procedure_name'      => $master->name,
                    'procedure_code'      => $procedureCode,
                    'speciality_id'       => $specialityId,
                    'procedure_master_id' => $master->id,
                    'assign_doctor'       => null,
                    'description'         => $master->description ?? '',
                    'estimated_time'      => $master->duration . ' minutes',
                    'cost'                => $master->cost ?? 0,
                    'status'              => 'inactive',
                    'hospital_id'         => $this->hospitalId,
                    'organization_id'     => $this->organizationId,
                ];

                $this->procedureService->createProcedure($procedureData);
                $createdCount++;
            }

            DB::commit();
            
            Flux::modal('bulk-add-procedure')->close();
            $this->dispatch('reloadProcedures');
            $this->resetInput();
            
            $this->dispatch('toast',
                type: 'success',
                message: "{$createdCount} procedures added successfully!"
            );
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save procedures: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('save', 'Failed to save procedures: ' . $e->getMessage());
            $this->dispatch('toast',
                type: 'error',
                message: 'Failed to save procedures: ' . $e->getMessage()
            );
        }
    }

    public function getSelectedProceduresDetails()
    {
        if (empty($this->selectedProcedures)) {
            return collect();
        }

        return ProcedureMaster::whereIn('id', $this->selectedProcedures)->get();
    }

    public function render()
    {
        $procedures = ProcedureMaster::query()
            ->when($this->procedureSearch, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->procedureSearch . '%')
                        ->orWhere('description', 'like', '%' . $this->procedureSearch . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $selectedProceduresDetails = $this->getSelectedProceduresDetails();

        return view(
            'livewire.admin.organization.hospital.procedure.bulk-add-procedure',
            compact('procedures', 'selectedProceduresDetails')
        );
    }
}