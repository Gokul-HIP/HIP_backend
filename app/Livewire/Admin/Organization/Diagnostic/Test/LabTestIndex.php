<?php

namespace App\Livewire\Admin\Organization\Diagnostic\Test;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Services\LabTestService;
use Illuminate\Support\Facades\Storage;
use App\Models\DiagnosticLabTest;

class LabTestIndex extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $diagnosticId;
    public $search = '';
    public $statusFilter = 'all';
    public $locationFilter = 'all';
    public $categoryFilter = 'all';
    public $labTestId;

    protected $labTestService;

    public function boot(LabTestService $labTestService)
    {
        $this->labTestService = $labTestService;
    }

    #[On('relodLabTest')]
    public function relodLabTest()
    {
        $this->resetPage();
    }

    public function editLabTest($id)
    {
        $this->dispatch('editLabTest', $id);
    }

    public function deleteLabTest($id)
    {
        $this->labTestId = $id;
        Flux::modal('delete-lab-test')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-lab-test')->close();
    }

    public function destroy()
    {
        $test = $this->labTestService->findLabTest($this->labTestId);

        if ($test) {
           if(Storage::disk('public')->exists($test->test_image)){
            Storage::disk('public')->delete($test->test_image);
           }
           $test->delete();
        }
        
        Flux::modal('delete-lab-test')->close();
        $this->relodLabTest();

        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Lab test '.$test->test_name.' deleted successfully!'
        );

    }

    public function updatingCategoryFilter()
    {
        $this->resetPage();
    }

    protected function getPageName()
    {
        return 'labPage';
    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->statusFilter,
            'category' => $this->categoryFilter !== 'all' ? $this->categoryFilter : '',
        ];

        $diagnostic = $this->labTestService->getDiagnostic($this->diagnosticId);

        $labTests = $this->labTestService->searchLabTestsPaginated($this->diagnosticId, $filters, 10);

        // Get available categories from lab tests table for this diagnostic
        $availableCategories = DiagnosticLabTest::where('diagnostic_id', $this->diagnosticId)
            ->whereNotNull('test_category')
            ->distinct()
            ->pluck('test_category')
            ->filter()
            ->sort()
            ->values();
    
        return view('livewire.admin.organization.diagnostic.test.lab-test-index', [
            'labTests' => $labTests,
            'diagnostic' => $diagnostic,
            'availableCategories' => $availableCategories
        ]);
    }
}
