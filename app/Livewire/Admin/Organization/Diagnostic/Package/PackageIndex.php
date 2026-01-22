<?php

namespace App\Livewire\Admin\Organization\Diagnostic\Package;

use Livewire\Component;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Models\DiagnosticPackage;
use App\Models\Diagnostic;
use App\Services\PackageService;

class PackageIndex extends Component
{
    public $diagnosticId;
    public $packages;
    public $search = '';
    public $status = 'all';
    public $diagnostic;

    protected $packageService;

    public function boot(PackageService $packageService)
    {
        $this->packageService = $packageService;
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
        $this->diagnostic = $this->packageService->getDiagnostic($this->diagnosticId);
    }

    public $package_id;

    #[On('package-added')]
    #[On('package-updated')]
    public function refreshPackages()
    {
        $this->render();
    }

    public function edit($id)
    {
        $this->dispatch('edit-package', $id);
    }

    public function delete($id)
    {
        $this->package_id = $id;
        Flux::modal('delete-package')->show();
    }

    public function closeModal()
    {
        Flux::modal('delete-package')->close();
    }

    public function destroy()
    {
        $package = $this->packageService->findPackage($this->package_id);
        $packageName = $package->name;
        $this->packageService->deletePackage($this->package_id);
        Flux::modal('delete-package')->close();
        $this->refreshPackages();
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Package '.$packageName.' deleted successfully!'
        );
    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'status' => $this->status,
        ];

        $this->packages = $this->packageService->searchPackages($this->diagnosticId, $filters);

        return view('livewire.admin.organization.diagnostic.package.package-index');
    }
}
