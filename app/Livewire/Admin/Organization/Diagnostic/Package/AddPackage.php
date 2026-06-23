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

class AddPackage extends Component
{
    use WithFileUploads;

    public int $step = 1;
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

    public $status = false;

    public $is_home_service = false;

    protected $packageService;
    protected $labTestService;

    public function boot(PackageService $packageService, LabTestService $labTestService)
    {
        $this->packageService = $packageService;
        $this->labTestService = $labTestService;
    }

    #[On('open-add-package')]
    public function open($diagnosticId)
    {
        $this->resetInput();
        $this->diagnosticId = $diagnosticId;
        $this->diagnostic = $this->packageService->getDiagnostic($diagnosticId);
        $this->loadLabTests();
        Flux::modal('add-package')->show();
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

    public function mount()
    {
        // Mount without parameters - diagnosticId will be set when modal opens
    }

    public function removeImage()
    {
        $this->image = null;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function resetInput()
    {
        $this->reset(['step', 'name', 'code', 'description', 'preparation_instruction', 'terms_and_conditions', 'price', 'discount', 'weight', 'image', 'status', 'is_home_service', 'selected_lab_test_ids', 'search']);
        $this->step = 1;
        $this->status = false;
        $this->is_home_service = false;
        $this->search = '';
        $this->resetErrorBag();
        $this->resetValidation();
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-package')->close();
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
                'selected_lab_test_ids' => 'required|array|min:1',
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

    public function addPackage()
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
            'diagnostic_id' => $this->diagnosticId,
            'lab_tests' => $this->selected_lab_test_ids,
        ];

        $this->packageService->createPackage($data, $this->image);

        $this->resetInput();
        Flux::modal('add-package')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Package '.$packageName.' added successfully!'
        );
        $this->dispatch('$refresh');
        $this->dispatch('package-added');
    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.package.add-package');
    }
}
