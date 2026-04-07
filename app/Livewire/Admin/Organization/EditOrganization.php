<?php

namespace App\Livewire\Admin\Organization;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Services\OrganizationService;
use App\Models\LocationMaster;

class EditOrganization extends Component
{
    use WithFileUploads;

    #[Rule("required")]
    public $org_name;

    #[Rule("required")]
    public $org_city;

    #[Rule("required")]
    public $org_address;

    #[Rule("nullable|image|max:2048")]
    public $org_logo;

    public bool $status = false;  // ← typed as bool
    public $org_id;
    public $old_logo_path;
    public $remove_image = false;
    public array $availableCities = [];

    protected $organizationService;

    public function boot(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function render()
    {
        return view('livewire.admin.organization.edit-organization');
    }

    public function mount(): void
    {
        $this->availableCities = LocationMaster::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->select('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->toArray();
    }

    // REMOVED #[On('statusChanged')] — direct wire:model handles it now

    #[On('editOrg')]
    public function editOrg($id)
    {
        $this->resetInput();

        $org = $this->organizationService->findOrganization($id);

        $this->org_id        = $id;
        $this->org_name      = $org->name;
        $this->org_city      = $org->city;
        $this->org_address   = $org->address;
        $this->old_logo_path = $org->logo;
        $this->status        = $org->status === 'active'; // true or false
        $this->remove_image  = false;
        $this->org_logo      = null;

        Flux::modal('edit-organization')->show();
    }

    public function removeImage()
    {
        $this->org_logo     = null;
        $this->remove_image = true;
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->org_logo     = null;
        $this->remove_image = false;
        $this->dispatch('reset-file-input');
    }

    public function resetInput()
    {
        $this->reset(['org_name', 'org_city', 'org_address', 'org_logo',
                      'old_logo_path', 'remove_image', 'org_id']);
        $this->status       = false;
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        $this->resetInput();
        Flux::modal('edit-organization')->close();
    }

    public function messages()
    {
        return [
            'org_name.required'    => 'Organization Name field is required.',
            'org_city.required'    => 'City field is required.',
            'org_address.required' => 'Address field is required.',
            'org_logo.image'       => 'Only image files are allowed.',
            'org_logo.max'         => 'The logo may not be greater than 2MB.',
        ];
    }

    public function updateOrg()
    {
        $this->validate();

        // Debug: dd($this->status); ← uncomment temporarily to verify value

        $data = [
            'name'    => $this->org_name,
            'city'    => $this->org_city,
            'address' => $this->org_address,
            'status'  => $this->status ? 'active' : 'inactive',
        ];

        $org = $this->organizationService->updateOrganization(
            $this->org_id,
            $data,
            $this->org_logo,
            $this->remove_image
        );

        Flux::modal('edit-organization')->close();
        $this->resetInput();

        $this->dispatch('toast', type: 'success', message: 'Organization ' . $org->name . ' updated successfully!');
        $this->dispatch('relodeOrg');
    }
}