<?php

namespace App\Livewire\Admin\Organization\Pharmacy\Products;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Models\Pharmacy;
use App\Models\MedicineMaster;
use App\Models\PharmacyProducts;
use App\Services\PharmacyProductService;

class AddBulkMedicines extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    
    public int $step = 1;
    public ?int $pharmacyId = null;
    public ?int $organizationId = null;
    public string $medicineSearch = '';
    public array $selectedMedicines = [];
    public array $toRemove = [];
    public bool $selectAllToRemove = false;
    public int $modalKey = 0;

    protected $pharmacyProductService;

    public function boot(PharmacyProductService $pharmacyProductService)
    {
        $this->pharmacyProductService = $pharmacyProductService;
    }

    #[On('open-bulk-add-medicines')]
    public function open(int $pharmacyId)
    {
        $this->resetInput();
        $this->resetPage();

        $this->pharmacyId = $pharmacyId;

        $pharmacy = Pharmacy::find($pharmacyId);
        if ($pharmacy) {
            $this->organizationId = $pharmacy->organization_id;
        }

        Flux::modal('bulk-add-medicines')->show();
    }

    public function resetInput()
    {
        $this->reset([
            'step',
            'medicineSearch',
            'selectedMedicines',
            'selectAllToRemove',
            'toRemove',
        ]);
        $this->resetErrorBag();
        $this->resetPage();
        $this->step = 1;
        $this->modalKey++;
    }

    public function updatedMedicineSearch()
    {
        $this->resetPage();
    }

    public function getMedicinesProperty()
    {
        return MedicineMaster::query()
            ->when($this->medicineSearch, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->medicineSearch . '%')
                        ->orWhere('code', 'like', '%' . $this->medicineSearch . '%')
                        ->orWhere('category', 'like', '%' . $this->medicineSearch . '%')
                        ->orWhere('brand_name', 'like', '%' . $this->medicineSearch . '%');
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
    }

    public function updatedToRemove()
    {
        $this->selectAllToRemove =
            count($this->toRemove) > 0 &&
            count($this->toRemove) === count($this->selectedMedicines);
    }

    public function updatedSelectAllToRemove($value)
    {
        if ($this->step !== 2) {
            return;
        }

        $this->toRemove = $value
            ? $this->selectedMedicines
            : [];
    }

    public function toggleSelectAllMedicines()
    {
        $visibleIds = $this->medicines->pluck('id')->toArray();
        
        // Filter out already added medicines
        $availableIds = array_filter($visibleIds, function($medicineId) {
            return !$this->isMedicineAlreadyAdded($medicineId);
        });

        $allAvailableSelectedOnPage =
            count($availableIds) > 0 &&
            count(array_diff($availableIds, $this->selectedMedicines)) === 0;

        if ($allAvailableSelectedOnPage) {
            // Unselect only current page (available medicines)
            $this->selectedMedicines = array_values(
                array_diff($this->selectedMedicines, $availableIds)
            );
        } else {
            // Select only available medicines on current page (exclude already added)
            $this->selectedMedicines = array_unique(
                array_merge($this->selectedMedicines, $availableIds)
            );
        }
    }

    public function getIsAllMedicinesSelectedOnPageProperty()
    {
        $visibleIds = $this->medicines->pluck('id')->toArray();
        
        // Filter out already added medicines
        $availableIds = array_filter($visibleIds, function($medicineId) {
            return !$this->isMedicineAlreadyAdded($medicineId);
        });

        if (empty($availableIds)) {
            return false;
        }

        return count(array_diff($availableIds, $this->selectedMedicines)) === 0;
    }

    public function toggleMedicine($medicineId)
    {
        // FIXED: Prevent toggling if already added
        if ($this->isMedicineAlreadyAdded($medicineId)) {
            return;
        }
        
        if (in_array($medicineId, $this->selectedMedicines)) {
            $this->selectedMedicines = array_values(
                array_diff($this->selectedMedicines, [$medicineId])
            );
        } else {
            $this->selectedMedicines[] = $medicineId;
            $this->selectedMedicines = array_values($this->selectedMedicines);
        }
    }

    public function deleteSelected()
    {
        if (empty($this->toRemove)) {
            return;
        }

        $this->selectedMedicines = array_values(
            array_diff($this->selectedMedicines, $this->toRemove)
        );

        $this->toRemove = [];
        $this->selectAllToRemove = false;

        if (empty($this->selectedMedicines)) {
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
                'selectedMedicines' => 'required|array|min:1',
            ], [
                'selectedMedicines.required' => 'Please select at least one medicine',
                'selectedMedicines.min' => 'Please select at least one medicine',
            ]);

            // Check for already existing medicines
            $existingMedicines = $this->checkExistingMedicines();
            
            if (!empty($existingMedicines)) {
                $medicineNames = implode(', ', array_column($existingMedicines, 'name'));
                
                $this->dispatch('toast',
                    type: 'error',
                    message: "The following medicines already exist: {$medicineNames}. Please remove them before proceeding."
                );
                
                return;
            }
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

    public function checkExistingMedicines()
    {
        $existingMedicines = [];
        
        $medicineMasters = MedicineMaster::whereIn('id', $this->selectedMedicines)->get();
        
        foreach ($medicineMasters as $master) {
            // Check by medicine_master_id
            $existsByMasterId = PharmacyProducts::where('pharmacy_id', $this->pharmacyId)
                ->where('medicine_master_id', $master->id)
                ->exists();
            
            // Also check by name to catch older records
            $existsByName = PharmacyProducts::where('pharmacy_id', $this->pharmacyId)
                ->where('product_name', $master->name)
                ->exists();
            
            if ($existsByMasterId || $existsByName) {
                $existingMedicines[] = [
                    'id' => $master->id,
                    'name' => $master->name
                ];
            }
        }
        
        return $existingMedicines;
    }

    public function isMedicineAlreadyAdded($medicineMasterId)
    {
        if (!$this->pharmacyId) {
            return false;
        }

        // Get the master medicine
        $master = MedicineMaster::find($medicineMasterId);
        
        if (!$master) {
            return false;
        }

        // Check by medicine_master_id first (preferred method)
        $existsByMasterId = PharmacyProducts::where('pharmacy_id', $this->pharmacyId)
            ->where('medicine_master_id', $medicineMasterId)
            ->exists();

        if ($existsByMasterId) {
            return true;
        }

        // Also check by name to catch medicines added before master_id was implemented
        $existsByName = PharmacyProducts::where('pharmacy_id', $this->pharmacyId)
            ->where('product_name', $master->name)
            ->exists();

        return $existsByName;
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('bulk-add-medicines')->close();
    }

    public function save()
    {
        $this->validate([
            'selectedMedicines' => 'required|array|min:1',
        ]);

        try {
            $result = $this->pharmacyProductService->createBulkProductsFromMedicineMaster(
                $this->pharmacyId,
                $this->selectedMedicines
            );

            Flux::modal('bulk-add-medicines')->close();
            $this->dispatch('refresh-products');
            $this->resetInput();
            
            $message = "{$result['created']} medicine(s) added successfully!";
            if ($result['skipped'] > 0) {
                $message .= " {$result['skipped']} medicine(s) already exist and were skipped.";
            }

            $this->dispatch(
                'toast',
                type: 'success',
                message: $message
            );
            
        } catch (\Exception $e) {
            $this->addError('save', 'Failed to save medicines: ' . $e->getMessage());
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Failed to save medicines: ' . $e->getMessage()
            );
        }
    }

    public function getSelectedMedicinesDetailsProperty()
    {
        if (empty($this->selectedMedicines)) {
            return collect();
        }

        return MedicineMaster::whereIn('id', $this->selectedMedicines)->get();
    }

    public function render()
    {
        $medicines = $this->medicines;
        $selectedMedicinesDetails = $this->getSelectedMedicinesDetailsProperty();

        return view(
            'livewire.admin.organization.pharmacy.products.add-bulk-medicines',
            compact('medicines', 'selectedMedicinesDetails')
        );
    }
}