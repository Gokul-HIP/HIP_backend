<?php

namespace App\Livewire\Admin\Organization\Diagnostic\Package;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Services\PackageService;
use App\Services\LabTestService;
use App\Models\Diagnostic;
use App\Support\DiscountPrice;

class EditPackage extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public int $editSessionKey = 0;
    public $packageId;
    public $diagnosticId;
    public $diagnostic;
    public $labTests = [];
    public array $selected_lab_test_ids = [];
    public $search = '';

    #[Rule('required')]
    public $name;

    #[Rule('nullable')]
    public $code;

    #[Rule('nullable')]
    public $description;

    #[Rule('nullable|string')]
    public $preparation_instruction;

    #[Rule('nullable|string')]
    public $terms_and_conditions;

    #[Rule('nullable|numeric|min:0')]
    public $price;

    #[Rule('nullable|numeric|min:0|max:100')]
    public $discount;

    #[Rule('nullable|numeric|min:0')]
    public $weight;

    #[Rule('nullable|image|max:2048')]
    public $image;

    public $old_image;
    public $remove_image = false;
    public $status = false;

    public $is_home_service = false;

    protected $packageService;
    protected $labTestService;

    public function boot(PackageService $packageService, LabTestService $labTestService)
    {
        $this->packageService = $packageService;
        $this->labTestService = $labTestService;
    }

    #[On('edit-package')]
    public function editPackage($id)
    {
        $this->resetInput();

        $package = $this->packageService->findPackage((int) $id);
        $this->diagnosticId = $package->diagnostic_id;
        $this->diagnostic = $this->packageService->getDiagnostic($this->diagnosticId);
        $this->loadLabTests();

        $this->name = $package->name;
        $this->code = $package->code;
        $this->description = $package->description;
        $this->preparation_instruction = $package->preparation_instruction;
        $this->terms_and_conditions = $package->terms_and_conditions;
        $this->price = $package->price;
        $this->discount = $package->discount;
        $this->weight = $package->weight;
        $this->old_image = $package->image;
        $this->image = null;
        $this->remove_image = false;
        $this->status = $package->status === 'active';
        $this->is_home_service = (bool) $package->is_home_service;

        $labTests = $package->lab_tests;
        if (empty($labTests)) {
            $this->selected_lab_test_ids = [];
        } elseif (is_array($labTests)) {
            $this->selected_lab_test_ids = array_map('intval', $labTests);
        } elseif (is_string($labTests)) {
            $decoded = json_decode($labTests, true);
            $this->selected_lab_test_ids = is_array($decoded) ? array_map('intval', $decoded) : [];
        } else {
            $this->selected_lab_test_ids = [];
        }

        $this->step = 1;
        $this->packageId = (int) $id;
        $this->editSessionKey++;

        Flux::modal('edit-package')->show();
    }

    public function removeImage()
    {
        $this->image = null;
        $this->remove_image = true;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->image = null;
        $this->remove_image = false;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function resetInput()
    {
        $this->reset([
            'step',
            'packageId',
            'diagnosticId',
            'diagnostic',
            'name',
            'code',
            'description',
            'preparation_instruction',
            'terms_and_conditions',
            'price',
            'discount',
            'weight',
            'image',
            'status',
            'is_home_service',
            'selected_lab_test_ids',
            'old_image',
            'remove_image',
            'search',
            'labTests',
        ]);
        $this->step = 1;
        $this->status = false;
        $this->is_home_service = false;
        $this->remove_image = false;
        $this->search = '';
        $this->resetErrorBag();
        $this->resetValidation();
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function loadLabTests()
    {
        $allLabTests = $this->labTestService->getAllLabTestsByDiagnostic($this->diagnosticId);
        
        if (!empty($this->search)) {
            $searchTerm = strtolower($this->search);
            $this->labTests = $allLabTests->filter(function ($labTest) use ($searchTerm) {
                return str_contains(strtolower($labTest->test_name), $searchTerm) ||
                       str_contains(strtolower($labTest->test_code ?? ''), $searchTerm) ||
                       str_contains(strtolower($labTest->category->category_name ?? ''), $searchTerm);
            })->values();
        } else {
            $this->labTests = $allLabTests;
        }
    }

    public function updatedSearch()
    {
        $this->loadLabTests();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-package')->close();
    }

    public function messages()
    {
        return [
            'name.required' => 'Package Name field is required.',
            'price.numeric' => 'Price must be a number.',
            'price.min' => 'Price must be at least 0.',
            'discount.numeric' => 'Discount price must be a number.',
            'discount.min' => 'Discount price must be at least 0.',
            'discount.lt' => 'Discount price must be less than the package price.',
            'weight.numeric' => 'Weight must be a number.',
            'weight.min' => 'Weight must be at least 0.',
            'image.image' => 'Only image files are allowed.',
            'image.max' => 'The image may not be greater than 2MB.',
            'selected_lab_test_ids.required' => 'Please select at least one lab test.',
            'selected_lab_test_ids.min' => 'Please select at least one lab test.',
        ];
    }

    public function next()
    {
        if ($this->step === 1) {
            $this->validate([
                'name' => 'required',
            ], [
                'name.required' => 'Package Name field is required.',
            ]);
        }

        if ($this->step === 2) {
            $this->validate([
                'selected_lab_test_ids' => 'required|array|min:1|',
            ], [
                'selected_lab_test_ids.required' => 'Please select at least one lab test.',
                'selected_lab_test_ids.min' => 'Please select at least one lab test.',
            ]);
        }

        if ($this->step < 3) {
            $this->step++;
        }
    }

    public function back()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function toggleLabTest($labTestId)
    {
        $labTestId = (int) $labTestId;
        
        if (in_array($labTestId, $this->selected_lab_test_ids)) {
            // Remove if already selected
            $this->selected_lab_test_ids = array_values(
                array_diff($this->selected_lab_test_ids, [$labTestId])
            );
        } else {
            $this->selected_lab_test_ids[] = $labTestId;
            $this->selected_lab_test_ids = array_values($this->selected_lab_test_ids);
        }
        
        $this->resetErrorBag('selected_lab_test_ids');
    }

    public function updatePackage()
    {
        $this->validate([
            'name' => 'required',
            'selected_lab_test_ids' => 'required|array|min:1',
            'discount' => 'nullable|numeric|min:0|lt:price',
        ], [
            'name.required' => 'Package Name field is required.',
            'selected_lab_test_ids.required' => 'Please select at least one lab test.',
            'selected_lab_test_ids.min' => 'Please select at least one lab test.',
        ]);

        $packageName = $this->name;
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'preparation_instruction' => $this->preparation_instruction,
            'terms_and_conditions' => $this->terms_and_conditions,
            'price' => $this->price,
            'discount' => DiscountPrice::forStorage((float) ($this->price ?? 0), $this->discount),
            'weight' => $this->weight,
            'status' => $this->status ? 'active' : 'inactive',
            'is_home_service' => (bool) $this->is_home_service,
            'lab_tests' => $this->selected_lab_test_ids,
        ];

        // Handle image removal - only remove if explicitly requested and no new image uploaded
        if ($this->remove_image && !$this->image) {
            $data['image'] = null;
        }

        $this->packageService->updatePackage($this->packageId, $data, $this->image);

        $this->step = 1;
        $this->resetInput();
        Flux::modal('edit-package')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Package '.$packageName.' updated successfully!'
        );
        $this->dispatch('package-updated');
    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.package.edit-package');
    }
}
