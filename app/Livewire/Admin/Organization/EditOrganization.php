<?php

namespace App\Livewire\Admin\Organization;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Rule;
use Livewire\Attributes\On;
use Flux\Flux;
use App\Services\OrganizationService;


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
    public $status;
    public $org_id;
    public $old_logo_path;
    public $remove_image = false;

    protected $organizationService;

    public function boot(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function render()
    {
        return view('livewire.admin.organization.edit-organization');
    }

    #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    #[On('editOrg')]
    public function editOrg($id)
    {
        // Reset all fields first
        $this->resetInput();
        
        // Load organization data
        $org = $this->organizationService->findOrganization($id);

        $this->org_id = $id;
        $this->org_name = $org->org_name;
        $this->org_city = $org->org_city;
        $this->org_address = $org->org_address;
        $this->old_logo_path = $org->org_logo;
        $this->status = $org->status === 'active';
        $this->remove_image = false;
        $this->org_logo = null;

        Flux::modal('edit-organization')->show();
    }

    public function removeImage()
    {
        $this->org_logo = null;
        $this->remove_image = true;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function restoreImage()
    {
        $this->org_logo = null;
        $this->remove_image = false;
        // Dispatch event to reset file input
        $this->dispatch('reset-file-input');
    }

    public function resetInput()
    {
        $this->reset(['org_name', 'org_city', 'org_address', 'org_logo', 'old_logo_path', 'remove_image', 'status', 'org_id']);
        $this->remove_image = false;
        $this->resetErrorBag();
        $this->resetValidation();
        // Dispatch event to reset file input
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
            'org_name.required' => 'Organization Name field is required.',
            'org_city.required' => 'City field is required.',
            'org_address.required' => 'Address field is required.',
            'org_logo.image' => 'Only image files are allowed.',
            'org_logo.max' => 'The logo may not be greater than 2MB.'
        ];
    }

    public function updateOrg()
    {
        $this->validate();

        $data = [
            'org_name' => $this->org_name,
            'org_city' => $this->org_city,
            'org_address' => $this->org_address,
            'status' => $this->status
        ];

        $org = $this->organizationService->updateOrganization(
            $this->org_id,
            $data,
            $this->org_logo,
            $this->remove_image
        );

        // Close modal and reset
        Flux::modal('edit-organization')->close();
        $this->resetInput();
        
        // Dispatch success messages
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Organization '.$org->org_name.' updated successfully!'
        );
        $this->dispatch('relodeOrg');
    }
}