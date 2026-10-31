<?php

namespace App\Livewire\Admin\Organization;

use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Flux\Flux;
use App\Services\OrganizationService;
use App\Models\LocationMaster;
use Illuminate\Validation\Rule as ValidationRule;


class AddOrganization extends Component
{
    use WithFileUploads;

    #[Rule("required")]
    public $org_name = '';

    public $org_code = '';

    #[Rule("required")]
    public $org_city = '';

    #[Rule("required")]
    public $org_address = '';

    #[Rule("required|image|max:2048")]
    public $org_logo;
    
    public $status = false;
    public array $availableCities = [];

    protected $organizationService;

    public function boot(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    public function render()
    {
        return view('livewire.admin.organization.add-organization');
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

    #[On('statusChanged')]
    public function updateStatus($value)
    {
        $this->status = $value;
    }

    public function addNewOrg()
    {
        $this->org_code = $this->blankCode($this->org_code);
        $this->validate();
        $this->validate([
            'org_code' => ['nullable', 'string', 'max:50', ValidationRule::unique('organizations', 'code')],
        ]);

        $data = [
            'name' => $this->org_name,
            'code' => $this->org_code,
            'city' => $this->org_city,
            'address' => $this->org_address,
            'status' => $this->status
        ];

        $this->organizationService->createOrganization($data, $this->org_logo);
        $this->resetInput();
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
        $this->org_code = '';
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

    private function blankCode(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}