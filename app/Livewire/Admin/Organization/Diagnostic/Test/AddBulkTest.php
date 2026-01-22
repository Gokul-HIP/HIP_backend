<?php

namespace App\Livewire\Admin\Organization\Diagnostic\Test;

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Flux\Flux;
use App\Services\LabTestService;
use App\Models\Diagnostic;
use App\Models\DiagnosticLabTest;
use App\Models\LabTestMaster;
use Illuminate\Support\Facades\Log;

class AddBulkTest extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';
    
    public int $step = 1;
    public ?int $diagnosticId = null;
    public ?int $organizationId = null;
    public string $testSearch = '';
    public array $selectedTests = [];
    // public bool $selectAll = false;
    public array $toRemove = [];
    public bool $selectAllToRemove = false;
    public int $modalKey = 0;
    protected $labTestService;

    public function boot(LabTestService $labTestService)
    {
        $this->labTestService = $labTestService;
    }

    #[On('open-bulk-add-test')]
    public function open(int $diagnosticId)
    {
        $this->resetInput();
        $this->resetPage();

        $this->diagnosticId = $diagnosticId;

        $diagnostic = $this->labTestService->getDiagnostic($diagnosticId);
        if ($diagnostic) {
            $this->organizationId = $diagnostic->organization_id;
        }

        Flux::modal('bulk-add-test')->show();
    }

    public function resetInput()
    {
        $this->reset([
            'step',
            'testSearch',
            'selectedTests',
            'selectAllToRemove',
            'toRemove',
        ]);
        $this->resetErrorBag();
        $this->resetPage();
        $this->step = 1;
        $this->modalKey++;
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('bulk-add-test')->close();
    }

    public function updatedTestSearch()
    {
        $this->resetPage();
    }

    // public function updatedSelectAll($value)
    // {
    //     if ($value) {
    //         $visibleIds = $this->tests->pluck('id')->toArray();
    //         $this->selectedTests = array_unique(array_merge($this->selectedTests, $visibleIds));
    //     } else {
    //         $visibleIds = $this->tests->pluck('id')->toArray();
    //         $this->selectedTests = array_values(array_diff($this->selectedTests, $visibleIds));
    //     }
    // }

    public function toggleSelectAll()
    {
        $visibleIds = $this->tests->pluck('id')->toArray();
        
        // Filter out already added tests
        $availableIds = array_filter($visibleIds, function($testId) {
            return !$this->isTestAlreadyAdded($testId);
        });

        $allAvailableSelectedOnPage =
            count($availableIds) > 0 &&
            count(array_diff($availableIds, $this->selectedTests)) === 0;

        if ($allAvailableSelectedOnPage) {
            // Unselect only current page (available tests)
            $this->selectedTests = array_values(
                array_diff($this->selectedTests, $availableIds)
            );
        } else {
            // Select only available tests on current page (exclude already added)
            $this->selectedTests = array_unique(
                array_merge($this->selectedTests, $availableIds)
            );
        }
    }

    public function getIsAllSelectedOnPageProperty()
    {
        $visibleIds = $this->tests->pluck('id')->toArray();
        
        // Filter out already added tests
        $availableIds = array_filter($visibleIds, function($testId) {
            return !$this->isTestAlreadyAdded($testId);
        });

        if (empty($availableIds)) {
            return false;
        }

        return count(array_diff($availableIds, $this->selectedTests)) === 0;
    }

    // public function updatedSelectedTests()
    // {
    //     $visibleIds = $this->tests->pluck('id')->toArray();

    //     $this->selectAll =
    //         count($this->selectedTests) > 0 &&
    //         count(array_diff($visibleIds, $this->selectedTests)) === 0;
    // }

    public function updatedSelectAllToRemove($value)
    {
        $this->toRemove = $value ? $this->selectedTests : [];
    }

    public function toggleTest($testId)
    {
        // Prevent toggling if already added
        if ($this->isTestAlreadyAdded($testId)) {
            return;
        }
        
        if (in_array($testId, $this->selectedTests)) {
            $this->selectedTests = array_diff($this->selectedTests, [$testId]);
        } else {
            $this->selectedTests[] = $testId;
        }
        
        $this->selectedTests = array_values($this->selectedTests);
    }

    public function updatedToRemove()
    {
        $this->selectAllToRemove =
            count($this->toRemove) > 0 &&
            count($this->toRemove) === count($this->selectedTests);
    }

    public function toggleForRemoval($testId)
    {
        if (in_array($testId, $this->toRemove)) {
            $this->toRemove = array_diff($this->toRemove, [$testId]);
        } else {
            $this->toRemove[] = $testId;
        }
        
        $this->toRemove = array_values($this->toRemove);
    }

    public function deleteSelected()
    {
        if (empty($this->toRemove)) {
            return;
        }

        $this->selectedTests = array_values(
            array_diff($this->selectedTests, $this->toRemove)
        );

        $this->toRemove = [];
        $this->selectAllToRemove = false;

        if (empty($this->selectedTests)) {
            // $this->selectAll = false;
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
                'selectedTests' => 'required|array|min:1',
            ], [
                'selectedTests.required' => 'Please select at least one test',
                'selectedTests.min' => 'Please select at least one test',
            ]);

            // Check for already existing tests
            $existingTests = $this->checkExistingTests();
            
            if (!empty($existingTests)) {
                $testNames = implode(', ', array_column($existingTests, 'name'));
                
                $this->dispatch('toast',
                    type: 'error',
                    message: "The following tests already exist: {$testNames}. Please remove them before proceeding."
                );
                
                return;
            }
        }

        if ($this->step < 2) {
            $this->step++;
            $this->toRemove = [];
        }
    }

    public function checkExistingTests()
    {
        $existingTests = [];
        
        $testMasters = LabTestMaster::whereIn('id', $this->selectedTests)->get();
        
        foreach ($testMasters as $master) {
            // Check by test name for the diagnostic
            $exists = DiagnosticLabTest::where('diagnostic_id', $this->diagnosticId)
                ->where('test_name', $master->test_name)
                ->exists();
            
            if ($exists) {
                $existingTests[] = [
                    'id' => $master->id,
                    'name' => $master->test_name
                ];
            }
        }
        
        return $existingTests;
    }

    public function isTestAlreadyAdded($testMasterId)
    {
        if (!$this->diagnosticId) {
            return false;
        }

        // Get the master test
        $master = LabTestMaster::find($testMasterId);
        
        if (!$master) {
            return false;
        }

        // Check by test name for the diagnostic
        $exists = DiagnosticLabTest::where('diagnostic_id', $this->diagnosticId)
            ->where('test_name', $master->test_name)
            ->exists();

        return $exists;
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
            'selectedTests' => 'required|array|min:1',
        ]);

        try {
            $createdTests = $this->labTestService->bulkCreateLabTests(
                $this->selectedTests,
                $this->diagnosticId,
                $this->organizationId
            );

            Flux::modal('bulk-add-test')->close();
            $this->resetInput();
            $this->dispatch('relodLabTest');
            
            $this->dispatch(
                'toast',
                type: 'success',
                message: count($createdTests) . " tests added successfully!"
            );
            
        } catch (\Exception $e) {
            Log::error('Failed to save tests: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch(
                'toast',
                type: 'error',
                message: 'Failed to save tests: ' . $e->getMessage()
            );
        }
    }

    public function getSelectedTestsDetailsProperty()
    {
        if (empty($this->selectedTests)) {
            return collect();
        }

        return $this->labTestService->getLabTestMastersByIds($this->selectedTests);
    }

    public function getTestsProperty()
    {
        $filters = ['search' => $this->testSearch];
        return $this->labTestService->getLabTestMasters($filters, 10);
    }

    public function render()
    {
        return view('livewire.admin.organization.diagnostic.test.add-bulk-test', [
            'tests' => $this->tests,
            'selectedTestsDetails' => $this->selectedTestsDetails,
        ]);
    }
}
