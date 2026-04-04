<?php

namespace App\Livewire\Admin\Organization;

use Flux\Flux;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Services\OrganizationService;

class DeleteOrganization extends Component
{
    public $org_id;

    protected $organizationService;

    public function boot(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function render()
    {
        return view('livewire.admin.organization.delete-organization');
    }

    #[On('delete')]
    public function delete($id)
    {
        $this->org_id = $id;
        Flux::modal('delete-org')->show();
    }

    public function closeModal()
    {
        $this->org_id = null;
        Flux::modal('delete-org')->close();
        $this->dispatch('relodeOrg');
    }

    public function destroy()
    {
        $organization = $this->organizationService->findOrganization($this->org_id);
        $orgName = $organization->name;

        $this->organizationService->deleteOrganization($this->org_id);

        $this->org_id = null;
        Flux::modal('delete-org')->close();

        $this->dispatch('toast', type: 'success', message: 'Organization ' . $orgName . ' deleted successfully!');
        $this->dispatch('relodeOrg');
    }
}