<?php

namespace App\Livewire\Admin\Organization;

use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Flux\Flux;
use App\Services\OrganizationService;


class AddOrganization extends Component
{
    use WithFileUploads;

    #[Rule("required")]
    public $org_name = '';

    #[Rule("required")]
    public $org_city = '';

    #[Rule("required")]
    public $org_address = '';

    #[Rule("required|image|max:2048")]
    public $org_logo;
    
    public $status = false;

    protected $organizationService;

    public function boot(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function render()
    {
        return view('livewire.admin.organization.add-organization');
    }

    #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function addNewOrg()
    {
        $this->validate();

        $data = [
            'org_name' => $this->org_name,
            'org_city' => $this->org_city,
            'org_address' => $this->org_address,
            'status' => $this->status
        ];

        $this->organizationService->createOrganization($data, $this->org_logo);

        Flux::modal('add-organization')->close();
        
        $this->dispatch(
            'toast',
            type: 'success',
            message: 'Organization ' . $this->org_name . ' added successfully!'
        );
        $this->dispatch('relodeOrg');
    }

    public function removeImage()
    {
        $this->org_logo = null;
    }

    public function resetInput()
    {
        $this->org_name = '';
        $this->org_city = '';
        $this->org_address = '';
        $this->org_logo = null;
        $this->status = false;
        
        $this->resetErrorBag();
        $this->resetValidation();
        
        $this->dispatch('reset-file-input');
    }

    public function closeModal()
    {
        Flux::modal('add-organization')->close();
    }

    public function messages()
    {
        return [
            'org_name.required' => 'Organization Name field is required.',
            'org_city.required' => 'City field is required.',
            'org_address.required' => 'Address field is required.',
            'org_logo.required' => 'Organization Logo field is required.',
            'org_logo.image' => 'Only image files are allowed.',
            'org_logo.max' => 'The logo may not be greater than 2MB.'
        ];
    }
}