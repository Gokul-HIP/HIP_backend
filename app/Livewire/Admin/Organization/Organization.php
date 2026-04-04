<?php

namespace App\Livewire\Admin\Organization;

use App\Services\OrganizationService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class Organization extends Component
{
    use WithPagination;

    public string $search = '';
    public string $location = 'all';
    public string $type = 'all';
    public string $status = 'all';

    protected $organizationService;

    public function boot(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    #[On('relodeOrg')]
    public function render()
    {
        $filters = [
            'search' => $this->search,
            'location' => $this->location,
            'status' => $this->status,
        ];

        $organizations = $this->organizationService->getOrganizationsPaginated($filters, 10);

        return view('livewire.admin.organization.organization', [
            'organization' => $organizations,
        ]);
    }
}
