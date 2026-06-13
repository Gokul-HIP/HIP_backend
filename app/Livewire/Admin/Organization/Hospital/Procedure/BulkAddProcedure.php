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
use Illuminate\Support\Str;

class BulkAddProcedure extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    
    public int $step = 1;
    public ?int $hospitalId = null;
    public ?int $organizationId = null;
    public string $procedureSearch = '';
    public array $selectedProcedures = [];
    public array $toRemove = [];
    public bool $selectAllToRemove = false;
    public int $modalKey = 0;

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

    public function toggleSelectAllProcedures()
    {
        $visibleIds = $this->procedures->pluck('id')->toArray();
        
        // Filter out already added procedures
        $availableIds = array_filter($visibleIds, function($procedureId) {
            return !$this->isProcedureAlreadyAdded($procedureId);
        });

        $allAvailableSelectedOnPage =
            count($availableIds) > 0 &&
            count(array_diff($availableIds, $this->selectedProcedures)) === 0;

        if ($allAvailableSelectedOnPage) {
            // Unselect only current page (available procedures)
            $this->selectedProcedures = array_values(
                array_diff($this->selectedProcedures, $availableIds)
            );
        } else {
            // Select only available procedures on current page (exclude already added)
            $this->selectedProcedures = array_unique(
                array_merge($this->selectedProcedures, $availableIds)
            );
        }
    }

    public function getIsAllProceduresSelectedOnPageProperty()
    {
        $visibleIds = $this->procedures->pluck('id')->toArray();
        
        // Filter out already added procedures
        $availableIds = array_filter($visibleIds, function($procedureId) {
            return !$this->isProcedureAlreadyAdded($procedureId);
        });

        if (empty($availableIds)) {
            return false;
        }

        return count(array_diff($availableIds, $this->selectedProcedures)) === 0;
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
            'selectAllToRemove',
            'toRemove',
        ]);
        $this->resetErrorBag();
        $this->resetValidation();
        $this->resetPage();
        $this->step = 1;
        $this->modalKey++;
    }

    #[On('modal-closed')]
    public function handleModalClosed($name)
    {
        if ($name === 'bulk-add-procedure') {
            $this->resetInput();
        }
    }

    public function updatedProcedureSearch()
    {
        $this->resetPage();
    }

    public function toggleProcedure($procedureId)
    {
        if (in_array($procedureId, $this->selectedProcedures)) {
            $this->selectedProcedures = array_diff($this->selectedProcedures, [$procedureId]);
        } else {
            $this->selectedProcedures[] = $procedureId;
        }
        
        $this->selectedProcedures = array_values($this->selectedProcedures);
    }

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
            $this->step = 1;
        }
    }

    public function updatedSelectedProcedures()
    {
        // This method can be kept for future hooks if needed
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

            // Check for already existing procedures
            $existingProcedures = $this->checkExistingProcedures();
            
            if (!empty($existingProcedures)) {
                $procedureNames = implode(', ', array_column($existingProcedures, 'name'));
                
                $this->dispatch('toast',
                    type: 'error',
                    message: "The following procedures already exist: {$procedureNames}. Please remove them before proceeding."
                );
                
                return; // Don't proceed to next step
            }
        }

        if ($this->step < 2) {
            $this->step++;
            $this->toRemove = [];
        }
    }

    public function checkExistingProcedures()
    {
        $existingProcedures = [];
        
        $procedureMasters = ProcedureMaster::whereIn('id', $this->selectedProcedures)->get();
        
        foreach ($procedureMasters as $master) {
            // Check if procedure already exists for this hospital and procedure master
            $exists = Procedure::where('hospital_id', $this->hospitalId)
                ->where('procedure_master_id', $master->id)
                ->exists();
            
            if ($exists) {
                $existingProcedures[] = [
                    'id' => $master->id,
                    'name' => $master->name
                ];
            }
        }
        
        return $existingProcedures;
    }

    public function isProcedureAlreadyAdded($procedureMasterId)
    {
        return Procedure::where('hospital_id', $this->hospitalId)
            ->where('procedure_master_id', $procedureMasterId)
            ->exists();
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
            $this->toRemove = [];
        }
    }

    public function generateProcedureCode($hospital)
    {
        do {
            $number = rand(1000, 9999);
            $code = Str::slug($hospital->name, '-')
                . '-PROC-' . date('Y') . '-' . $number;
        } while (Procedure::where('procedure_code', $code)->exists());

        return strtoupper($code);
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
                            'speciality_code'        => $this->generateSpecialityCode($hospital),
                            'department_category'    => $master->specialityMaster->name,
                            'status'                 => 'active',
                            'organization_id'        => $this->organizationId,
                            'speciality_description' => $master->specialityMaster->description ?? '',
                            'speciality_logo'        => 'default-speciality.png',
                        ]
                    );
                
                    $specialityId = $speciality->id;
                }

                // Generate unique code for each procedure
                $procedureCode = $this->generateProcedureCode($hospital);

                $procedureData = [
                    'procedure_name'      => $master->name,
                    'procedure_code'      => $procedureCode,
                    'speciality_id'       => $specialityId,
                    'procedure_master_id' => $master->id,
                    'assign_doctor'       => null,
                    'description'         => $master->description ?? '',
                    'estimated_time'      => $master->duration . ' minutes',
                    'cost'                => $master->cost ?? 0,
                    'discount'            => $master->discount ?? 0,
                    'status'              => 'active',
                    'hospital_id'         => $this->hospitalId,
                    'organization_id'     => $this->organizationId,
                    'recovery_time'       => $master->recovery_time ?? null,
                    'success_rate'        => $master->success_rate ?? null,
                    'hospitalization_days' => $master->hospitalization_days ?? null,
                    'image'               => $master->image ? basename($master->image) : null,
                ];

                try {
                    $this->procedureService->createBulkProcedure($procedureData, $master->image);
                    $createdCount++;
                } catch (\Illuminate\Database\QueryException $e) {
                    // Handle duplicate entry error
                    if ($e->errorInfo[1] == 1062) {
                        // Regenerate code and retry
                        $procedureData['procedure_code'] = $this->generateProcedureCode($hospital);
                        $this->procedureService->createBulkProcedure($procedureData, $master->image);
                        $createdCount++;
                    } else {
                        throw $e;
                    }
                }
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

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('bulk-add-procedure')->close();
        $this->dispatch('reloadProcedures');
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