<?php

namespace App\Livewire\Admin\Organization\Diagnostic\Test;

use Livewire\Component;
use Flux\Flux;
use Livewire\Attributes\Rule;
use Livewire\WithFileUploads;
use App\Services\LabTestService;
use Illuminate\Support\Facades\Log;
use App\Models\MasterLabtestCategory;

class AddLabTest extends Component
{   
    use WithFileUploads;
    
    public $diagnosticId;
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

    #[Rule('required|image|max:2048')]
    public $test_image;
    public $status = false;

    protected $labTestService;

    public function boot(LabTestService $labTestService)
    {
        $this->labTestService = $labTestService;
    }

    public function mount($diagnosticId)
    {
        $this->diagnosticId = $diagnosticId;
        $diagnostic = $this->labTestService->getDiagnostic($diagnosticId);
        $this->diagnosticName = $diagnostic->name ?? '';
    }

    public function getCategoriesProperty()
    {
        return MasterLabtestCategory::all();
    }

    public function removeImage()
    {
        $this->test_image = null;
    }

    public function resetInput()
    {
        $this->reset(['test_name', 'test_category', 'test_code', 'test_description', 'test_price', 'test_discount', 'test_image']);
        $this->test_category = '';
        $this->status = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->removeImage();
        $this->dispatch('reset-file-input');
    }
    
    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('add-lab-test')->close();
    }

    public function saveLabTest()
    {
        $this->validate();

        $testName = $this->test_name;
        $data = [
            'diagnostic_id' => $this->diagnosticId,
            'test_name' => $this->test_name,
            'test_category' => $this->test_category,
            'test_code' => $this->test_code,
            'test_description' => $this->test_description,
            'test_price' => $this->test_price,
            'test_discount' => $this->test_discount,
            'status' => $this->status ? 'active' : 'inactive',
        ];

        $test = $this->labTestService->createLabTest($data, $this->test_image);

        $this->dispatch('relodLabTest');
        $this->resetInput();
        Flux::modal('add-lab-test')->close();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'New '.$testName.' lab test added successfully!'
        );

    }

    public function messages()
    {
        return [
            'test_name.required' => 'Test name is required',
            'test_category.required' => 'Test category is required',
            'test_category.exists' => 'Selected category is invalid',
            'test_price.required' => 'Test price is required',
            'test_image.required' => 'Test image is required',
            'test_image.image' => 'Test image must be an image',
            'test_image.max' => 'Test image must be less than 2MB',
        ];
    }   


    public function render()
    {
        return view('livewire.admin.organization.diagnostic.test.add-lab-test');
    }
}
