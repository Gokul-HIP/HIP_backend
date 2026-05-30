<?php

namespace App\Livewire\Admin\Organization\Diagnostic\DiseasePackage;

use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Services\DiseasePackageService;

class DiseasePackageIndex extends Component
{
    public $diagnosticId;
    public $packages;
    public $search = '';
    public $status = 'all';
    public $diagnostic;
    public $package_id;

    protected $diseasePackageService;

    public function boot(DiseasePackageService $diseasePackageService)
    {
        $this->diseasePackageService = $diseasePackageService;
    }

    public function updatingSearch()
    {
        $this->render();
    }

    public function updatingStatus()
    {
        $this->render();
    }

    public function mount($diagnosticId)
    {
        $this->diagnosticId = $diagnosticId;
        $this->diagnostic = $this->diseasePackageService->getDiagnostic($this->diagnosticId);
    }

    #[On('disease-package-added')]
    #[On('disease-package-updated')]
    public function refreshPackages()
    {
        $this->render();
    }

    public function edit($id)
    {
        $this->dispatch('edit-disease-package', id: $id);
    }

    public function delete($id)
    {
        $this->package_id = $id;
        Flux::modal('delete-disease-package')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-disease-package')->close();
    }

    public function destroy()
    {
        $package = $this->diseasePackageService->findPackage($this->package_id);
        $packageName = $package->name;
        $this->diseasePackageService->deletePackage($this->package_id);
        Flux::modal('delete-disease-package')->close();
        $this->refreshPackages();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Disease package ' . $packageName . ' deleted successfully!'
        );
    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->status,
        ];

        $this->packages = $this->diseasePackageService->searchPackages($this->diagnosticId, $filters);

        return view('livewire.admin.organization.diagnostic.disease-package.disease-package-index');
    }
}
