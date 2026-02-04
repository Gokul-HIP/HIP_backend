<?php

namespace App\Livewire\Admin\WellnessServices;

use Livewire\Component;
use App\Models\WellnessCenters;
use App\Models\MasterWellnessCategories;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $locationFilter = 'all';
    public $typeFilter = 'all';
    public $statusFilter = 'all';
    public $wellnessCenterId;
    public $wellnessCenterName;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingLocationFilter()
    {
        $this->resetPage();
    }

    public function updatingTypeFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    #[On('refreshWellnessCenters')]
    public function refreshWellnessCenters()
    {
        // This will trigger a re-render with fresh data
    }

    public function deleteWellnessCenter($id)
    {
        try {
            $wellnessCenter = WellnessCenters::findOrFail($id);
            $this->wellnessCenterId = $id;
            $this->wellnessCenterName = $wellnessCenter->centre_name ?? 'this wellness centre';
            Flux::modal('delete-wellness-center')->show();
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Wellness centre not found.');
        }
    }

    public function destroy()
    {
        try {
            if (!$this->wellnessCenterId) {
                throw new \Exception('Wellness centre ID is required.');
            }

            $wellnessCenter = WellnessCenters::findOrFail($this->wellnessCenterId);
            $centreName = $wellnessCenter->centre_name ?? 'Wellness Centre';

            // Delete all associated files
            $fileFields = [
                'registration_certificate',
                'ownership_proof',
                'accreditation_certificate',
                'fire_safety_certificate',
            ];

            foreach ($fileFields as $field) {
                if ($wellnessCenter->$field) {
                    $filePath = 'wellness-centers/documents/' . $wellnessCenter->$field;
                    if (Storage::disk('public')->exists($filePath)) {
                        Storage::disk('public')->delete($filePath);
                    }
                }
            }

            // Delete the wellness center record
            $wellnessCenter->delete();

            // Reset properties
            $this->wellnessCenterId = null;
            $this->wellnessCenterName = null;

            // Close modal and show success message
            Flux::modal('delete-wellness-center')->close();
            $this->dispatch('toast', type: 'success', message: 'Wellness centre "' . $centreName . '" deleted successfully!');
            $this->dispatch('refreshWellnessCenters');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->closeModal();
            $this->dispatch('toast', type: 'error', message: 'Wellness centre not found.');
        } catch (\Exception $e) {
            $this->closeModal();
            $this->dispatch('toast', type: 'error', message: 'Failed to delete wellness centre: ' . $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->wellnessCenterId = null;
        $this->wellnessCenterName = null;
        Flux::modal('delete-wellness-center')->close();
    }

    public function render()
    {
        $query = WellnessCenters::with('wellnessCategory')
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('centre_name', 'like', '%' . $this->search . '%')
                        ->orWhere('city', 'like', '%' . $this->search . '%')
                        ->orWhere('state', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_person_name', 'like', '%' . $this->search . '%')
                        ->orWhere('contact_person_mobile', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->locationFilter !== 'all', function ($q) {
                $q->where('city', $this->locationFilter);
            })
            ->when($this->typeFilter !== 'all', function ($q) {
                $q->where('centre_type', $this->typeFilter);
            })
            ->when($this->statusFilter !== 'all', function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy('id', 'desc');

        $wellnessCenters = $query->paginate(10);

        $availableLocations = WellnessCenters::whereNotNull('city')
            ->distinct()
            ->pluck('city')
            ->filter()
            ->sort()
            ->values();

        // Get available types from categories that are actually used in wellness centers
        $usedCategoryIds = WellnessCenters::whereNotNull('centre_type')
            ->distinct()
            ->pluck('centre_type')
            ->filter()
            ->values();
        
        $availableTypes = MasterWellnessCategories::whereIn('id', $usedCategoryIds)
            ->orderBy('parent_category')
            ->get();

        $availableStatuses = WellnessCenters::whereNotNull('status')
            ->distinct()
            ->pluck('status')
            ->filter()
            ->sort()
            ->values();

        $totalCenters = WellnessCenters::count();
        
        // Get category IDs for statistics
        $physicalHealthCategoryIds = MasterWellnessCategories::where('parent_category', 'like', '%Physical Health%')
            ->orWhere('parent_category', 'like', '%Physical%')
            ->pluck('id')
            ->toArray();
        
        $mentalHealthCategoryIds = MasterWellnessCategories::where('parent_category', 'like', '%Mental Health%')
            ->orWhere('parent_category', 'like', '%Mental%')
            ->pluck('id')
            ->toArray();
        
        $employeeCoachingCategoryIds = MasterWellnessCategories::where('parent_category', 'like', '%Employee Coaching%')
            ->orWhere('parent_category', 'like', '%Employee%')
            ->orWhere('parent_category', 'like', '%Coaching%')
            ->pluck('id')
            ->toArray();
        
        $activePhysicalHealth = WellnessCenters::where('status', 'active')
            ->whereIn('centre_type', $physicalHealthCategoryIds)
            ->count();
        
        $activeMentalHealth = WellnessCenters::where('status', 'active')
            ->whereIn('centre_type', $mentalHealthCategoryIds)
            ->count();
        
        $activeEmployeeCoaching = WellnessCenters::where('status', 'active')
            ->whereIn('centre_type', $employeeCoachingCategoryIds)
            ->count();

        return view('livewire.admin.wellness-services.index', [
            'wellnessCenters' => $wellnessCenters,
            'availableLocations' => $availableLocations,
            'availableTypes' => $availableTypes,
            'availableStatuses' => $availableStatuses,
            'totalCenters' => $totalCenters,
            'activePhysicalHealth' => $activePhysicalHealth,
            'activeMentalHealth' => $activeMentalHealth,
            'activeEmployeeCoaching' => $activeEmployeeCoaching,
        ]);
    }
}
