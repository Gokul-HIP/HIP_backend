<?php

namespace App\Livewire\Admin\Organization\Diagnostic\DiseasePackage;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\SpecialitiesMaster;
use App\Services\DiseasePackageService;
use App\Services\LabTestService;

class EditDiseasePackage extends Component
{
    use WithFileUploads;

    public int $step = 1;
    public $packageId;
    public $diagnosticId;
    public $diagnostic;
    public $labTests = [];
    public array $selected_lab_test_ids = [];
    public $search = '';
    public $show_disease_dropdown = false;

    #[Rule('required')]
    public $name;

    public $disease_id = null;

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

    public $old_image;
    public $remove_image = false;
    public $status = false;
    public $is_home_service = false;

    protected $diseasePackageService;
    protected $labTestService;

    public function boot(DiseasePackageService $diseasePackageService, LabTestService $labTestService)
    {
        $this->diseasePackageService = $diseasePackageService;
        $this->labTestService = $labTestService;
    }

    #[On('edit-disease-package')]
    public function editDiseasePackage($id)
    {
        $this->resetInput();
        $this->packageId = $id;

        $package = $this->diseasePackageService->findPackage($id);
        $this->diagnosticId = $package->diagnostic_id;
        $this->diagnostic = $this->diseasePackageService->getDiagnostic($this->diagnosticId);
        $this->loadLabTests();

        $this->name = $package->name;
        $this->disease_id = $package->disease_id;
        $this->code = $package->code;
        $this->description = $package->description;
        $this->price = $package->price;
        $this->discount = $package->discount;
        $this->weight = $package->weight;
        $this->old_image = $package->image;
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

        Flux::modal('edit-disease-package')->show();
    }

    public function getFilteredDiseasesProperty()
    {
        $term = trim((string) $this->name);

        return SpecialitiesMaster::searchableDiseaseOptions($term, 15);
    }

    public function updatedName($value)
    {
        $this->show_disease_dropdown = true;

        if ($this->disease_id) {
            $selected = collect(SpecialitiesMaster::searchableDiseaseOptions())
                ->firstWhere('id', (int) $this->disease_id);

            if (! $selected || strcasecmp(trim((string) $value), $selected->name) !== 0) {
                $this->disease_id = null;
            }
        }
    }

    public function selectDisease($diseaseId, $diseaseName)
    {
        $this->disease_id = (int) $diseaseId;
        $this->name = $diseaseName;
        $this->show_disease_dropdown = false;
        $this->resetErrorBag('name');
    }

    public function hideDiseaseDropdown()
    {
        $this->show_disease_dropdown = false;
    }

    public function loadLabTests()
    {
        $allLabTests = $this->labTestService->getAllLabTestsByDiagnostic($this->diagnosticId);

        if (! empty($this->search)) {
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

    public function removeImage()
    {
        $this->image = null;
        $this->remove_image = true;
        $this->dispatch('reset-disease-package-file-input');
    }

    public function restoreImage()
    {
        $this->image = null;
        $this->remove_image = false;
        $this->dispatch('reset-disease-package-file-input');
    }

    public function resetInput()
    {
        $this->reset([
            'step', 'name', 'disease_id', 'code', 'description', 'price', 'discount', 'weight',
            'image', 'status', 'is_home_service', 'selected_lab_test_ids', 'old_image', 'remove_image',
            'search', 'show_disease_dropdown',
        ]);
        $this->step = 1;
        $this->status = false;
        $this->is_home_service = false;
        $this->remove_image = false;
        $this->search = '';
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('reset-disease-package-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-disease-package')->close();
    }

    public function next()
    {
        if ($this->step === 1) {
            $this->validate(['name' => 'required'], [
                'name.required' => 'Disease name is required.',
            ]);
        }

        if ($this->step === 2) {
            $this->validate(['selected_lab_test_ids' => 'required|array|min:1'], [
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
            $this->selected_lab_test_ids = array_values(
                array_diff($this->selected_lab_test_ids, [$labTestId])
            );
        } else {
            $this->selected_lab_test_ids[] = $labTestId;
            $this->selected_lab_test_ids = array_values($this->selected_lab_test_ids);
        }

        $this->resetErrorBag('selected_lab_test_ids');
    }

    public function updateDiseasePackage()
    {
        $this->validate([
            'name' => 'required',
            'selected_lab_test_ids' => 'required|array|min:1',
        ], [
            'name.required' => 'Disease name is required.',
            'selected_lab_test_ids.required' => 'Please select at least one lab test.',
            'selected_lab_test_ids.min' => 'Please select at least one lab test.',
        ]);

        $packageName = $this->name;
        $data = [
            'name' => trim($this->name),
            'disease_id' => $this->disease_id ?: null,
            'code' => $this->code,
            'description' => $this->description,
            'price' => $this->price,
            'discount' => $this->discount,
            'weight' => $this->weight,
            'status' => $this->status ? 'active' : 'inactive',
            'is_home_service' => (bool) $this->is_home_service,
            'lab_tests' => $this->selected_lab_test_ids,
        ];

        if ($this->remove_image && ! $this->image) {
            $data['image'] = null;
        }

        $this->diseasePackageService->updatePackage($this->packageId, $data, $this->image);

        $this->resetInput();
        Flux::modal('edit-disease-package')->close();
        $this->dispatch('toast', type: 'success', message: 'Disease package ' . $packageName . ' updated successfully!');
        $this->dispatch('disease-package-updated');
    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.disease-package.edit-disease-package');
    }
}
