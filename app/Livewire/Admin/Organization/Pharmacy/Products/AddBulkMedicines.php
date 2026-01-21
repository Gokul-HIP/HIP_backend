<?php

namespace App\Livewire\Admin\Organization\Pharmacy\Products;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Models\Pharmacy;
use App\Models\MedicineMaster;
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
        $this->step = 1;
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

        $allSelectedOnPage =
            count($visibleIds) > 0 &&
            count(array_diff($visibleIds, $this->selectedMedicines)) === 0;

        if ($allSelectedOnPage) {
            // Unselect only current page
            $this->selectedMedicines = array_values(
                array_diff($this->selectedMedicines, $visibleIds)
            );
        } else {
            // Select only current page
            $this->selectedMedicines = array_values(
                array_unique(
                    array_merge($this->selectedMedicines, $visibleIds)
                )
            );
        }
    }

    public function getIsAllMedicinesSelectedOnPageProperty()
    {
        $visibleIds = $this->medicines->pluck('id')->toArray();

        if (empty($visibleIds)) {
            return false;
        }

        return count(array_diff($visibleIds, $this->selectedMedicines)) === 0;
    }

    public function toggleMedicine($medicineId)
    {
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