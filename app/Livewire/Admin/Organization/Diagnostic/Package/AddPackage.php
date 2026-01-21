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

class AddPackage extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public $diagnosticId;
    public $diagnostic;
    public $labTests = [];
    public array $selected_lab_test_ids = [];

    #[Rule('required')]
    public $name;

    #[Rule('nullable')]
    public $code;

    #[Rule('nullable')]
    public $description;

    #[Rule('nullable|numeric|min:0')]
    public $price;

    #[Rule('nullable|numeric|min:0|max:100')]
    public $discount;

    #[Rule('nullable|numeric|min:0')]
    public $weight;

    #[Rule('nullable|image|max:2048')]
    public $image;

    public $status = false;

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
        $this->labTests = $this->labTestService->getAllLabTestsByDiagnostic($diagnosticId);
        Flux::modal('add-package')->show();
    }

    public function mount()
    {
        // Mount without parameters - diagnosticId will be set when modal opens
    }

    public function removeImage()
    {
        $this->image = null;
    }

    public function resetInput()
    {
        $this->reset(['step', 'name', 'code', 'description', 'price', 'discount', 'weight', 'image', 'status', 'selected_lab_test_ids']);
        $this->step = 1;
        $this->status = false;
        $this->resetErrorBag();
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
            'discount.numeric' => 'Discount must be a number.',
            'discount.min' => 'Discount must be at least 0.',
            'discount.max' => 'Discount cannot exceed 100.',
            'weight.numeric' => 'Weight must be a number.',
            'weight.min' => 'Weight must be at least 0.',
            'image.image' => 'Only image files are allowed.',
            'image.max' => 'The image may not be greater than 2MB.',
            'selected_lab_test_ids.required' => 'Please select at least one lab test.',
            'selected_lab_test_ids.min' => 'Please select at least one lab test.',
            'selected_lab_test_ids.max' => 'You can select maximum 4 lab tests.',
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
                'selected_lab_test_ids' => 'required|array|min:1|max:4',
            ], [
                'selected_lab_test_ids.required' => 'Please select at least one lab test.',
                'selected_lab_test_ids.min' => 'Please select at least one lab test.',
                'selected_lab_test_ids.max' => 'You can select maximum 4 lab tests.',
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
            // Add if not selected, but check max limit
            if (count($this->selected_lab_test_ids) >= 4) {
                $this->addError('selected_lab_test_ids', 'You can select maximum 4 lab tests.');
                return;
            }
            $this->selected_lab_test_ids[] = $labTestId;
            $this->selected_lab_test_ids = array_values($this->selected_lab_test_ids);
        }
        
        $this->resetErrorBag('selected_lab_test_ids');
    }

    public function addPackage()
    {
        $this->validate([
            'name' => 'required',
            'selected_lab_test_ids' => 'required|array|min:1|max:4',
        ], [
            'name.required' => 'Package Name field is required.',
            'selected_lab_test_ids.required' => 'Please select at least one lab test.',
            'selected_lab_test_ids.min' => 'Please select at least one lab test.',
            'selected_lab_test_ids.max' => 'You can select maximum 4 lab tests.',
        ]);

        $packageName = $this->name;
        $data = [
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'price' => $this->price,
            'discount' => $this->discount,
            'weight' => $this->weight,
            'status' => $this->status ? 'active' : 'inactive',
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
