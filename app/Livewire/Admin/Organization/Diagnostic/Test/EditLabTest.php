<?php

namespace App\Livewire\Admin\Organization\Diagnostic\Test;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Services\LabTestService;
use App\Models\MasterLabtestCategory;

class EditLabTest extends Component
{
    use WithFileUploads;
    
    public $labTestId;
    public $diagnosticName;
    public $organizationId;
    
    #[Rule('required')]
    public $test_name;

    #[Rule('required|exists:master_labtest_categories,id')]
    public $test_category;
    public $test_code;
    public $test_description;

    #[Rule('required')]
    public $test_price;
    public $test_discount;

    #[Rule('nullable|image|max:2048')]
    public $test_image;
    public $old_test_image;
    public $remove_image = false;
    public $test_status = false;

    protected $labTestService;

    public function boot(LabTestService $labTestService)
    {
        $this->labTestService = $labTestService;
    }

    #[On('editLabTest')]
    public function editLabTest($id)
    {
        // Reset all fields first
        $this->resetInput();
        
        // Load lab test data
        $labTest = $this->labTestService->findLabTest($id);
        $this->labTestId = $id;
        
        $diagnostic = $this->labTestService->getDiagnostic($labTest->diagnostic_id);
        $this->diagnosticName = $diagnostic->name ?? '';
        $this->organizationId = $labTest->organization_id;
        $this->test_name = $labTest->test_name;
        // Load category ID - if it's a name, find the ID, otherwise use the value as ID
        $categoryValue = $labTest->test_category;
        if (is_numeric($categoryValue)) {
            $this->test_category = $categoryValue;
        } else {
            // If it's a category name, find the ID
            $category = MasterLabtestCategory::where('category_name', $categoryValue)->first();
            $this->test_category = $category ? $category->id : '';
        }
        $this->test_code = $labTest->test_code;
        $this->test_description = $labTest->test_description;
        $this->test_price = $labTest->test_price;
        $this->test_discount = $labTest->test_discount;
        $this->old_test_image = $labTest->test_image;
        $this->test_image = null;
        $this->remove_image = false;
        $this->test_status = $labTest->test_status === 'active';

        Flux::modal('edit-lab-test')->show();
    }

    public function removeImage()
    {
        $this->test_image = null;
        $this->remove_image = true;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->test_image = null;
        $this->remove_image = false;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function resetInput()
    {
        $this->reset(['test_name', 'test_category', 'test_code', 'test_description', 'test_price', 'test_discount', 'test_image', 'old_test_image', 'remove_image']);
        $this->test_category = '';
        $this->test_status = false;
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function getCategoriesProperty()
    {
        return MasterLabtestCategory::all();
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-lab-test')->close();
    }

    public function updateLabTest()
    {
        $this->validate();

        $testName = $this->test_name;
        $data = [
            'test_name' => $this->test_name,
            'test_category' => $this->test_category,
            'test_code' => $this->test_code,
            'test_description' => $this->test_description,
            'test_price' => $this->test_price,
            'test_discount' => $this->test_discount,
            'test_status' => $this->test_status ? 'active' : 'inactive',
        ];

        // Handle image removal
        if ($this->remove_image && !$this->test_image) {
            $data['test_image'] = null;
        }

        $test = $this->labTestService->updateLabTest($this->labTestId, $data, $this->test_image);

        $this->resetInput();
        Flux::modal('edit-lab-test')->close();
        $this->dispatch('relodLabTest');
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Lab test '.$testName.' updated successfully!'
        );
        
    }

    public function messages()
    {
        return [
            'test_name.required' => 'Test name is required',
            'test_category.required' => 'Test category is required',
            'test_category.exists' => 'Selected category is invalid',
            'test_price.required' => 'Test price is required',
            'test_image.image' => 'Test image must be an image',
            'test_image.max' => 'Test image must be less than 2MB',
        ];
    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.test.edit-lab-test');
    }
}
