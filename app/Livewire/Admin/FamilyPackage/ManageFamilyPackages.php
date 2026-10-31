<?php

namespace App\Livewire\Admin\FamilyPackage;

use App\Livewire\Concerns\ManagesMembershipPackageDiscounts;
use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\Hospital;
use App\Services\FamilyPackageService;
use Flux\Flux;
use Livewire\Component;

class ManageFamilyPackages extends Component
{
    use ManagesMembershipPackageDiscounts;

    public $packageId;

    public $name = '';

    public $description = '';

    public $price = '';

    public $duration_days = 365;

    public $max_members = 1;

    public array $branch_ids = [];

    public array $terms_conditions = [['title' => '', 'description' => '']];

    public $sort_order = 0;

    public $is_active = true;

    protected FamilyPackageService $familyPackageService;

    public function boot(FamilyPackageService $familyPackageService): void
    {
        $this->familyPackageService = $familyPackageService;
    }

    public function render()
    {
        $packages = FamilyPackage::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $hospitals = Hospital::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        $branchNames = Hospital::query()
            ->whereIn('id', collect($packages)->pluck('branch_ids')->flatten()->filter()->unique())
            ->pluck('name', 'id');

        $benefitPackageIds = collect($packages)
            ->pluck('benefits')
            ->flatten(1)
            ->filter(fn ($item) => FamilyPackage::isPackageBenefit($item))
            ->pluck('diagnostic_package_id')
            ->filter()
            ->unique();

        $benefitPackageNames = $benefitPackageIds->isEmpty()
            ? collect()
            : DiagnosticPackage::query()->whereIn('id', $benefitPackageIds)->pluck('name', 'id');

        return view('livewire.admin.family-package.manage-family-packages', [
            'packages' => $packages,
            'hospitals' => $hospitals,
            'branchNames' => $branchNames,
            'benefitPackageNames' => $benefitPackageNames,
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        Flux::modal('family-package-form')->show();
    }

    public function openEditModal(int $id): void
    {
        $package = FamilyPackage::findOrFail($id);
        $this->packageId = $package->id;
        $this->name = $package->name;
        $this->description = $package->description ?? '';
        $this->price = $package->price;
        $this->duration_days = $package->duration_days;
        $this->max_members = $package->max_members;
        $this->branch_ids = array_values(array_map('intval', $package->branch_ids ?? []));
        $this->loadPaymentDiscounts($package);
        $this->terms_conditions = $package->terms_conditions ?: [['title' => '', 'description' => '']];
        $this->sort_order = $package->sort_order;
        $this->is_active = (bool) $package->is_active;
        Flux::modal('family-package-form')->show();
    }

    public function addTermsRow(): void
    {
        $this->terms_conditions[] = ['title' => '', 'description' => ''];
    }

    public function removeTermsRow(int $index): void
    {
        unset($this->terms_conditions[$index]);
        $this->terms_conditions = array_values($this->terms_conditions ?: [['title' => '', 'description' => '']]);
        if ($this->terms_conditions === []) {
            $this->terms_conditions = [['title' => '', 'description' => '']];
        }
    }

    public function toggleBranch(int $branchId): void
    {
        if (in_array($branchId, $this->branch_ids, true)) {
            $this->branch_ids = array_values(array_diff($this->branch_ids, [$branchId]));
        } else {
            $this->branch_ids[] = $branchId;
        }
    }

    public function savePackage(): void
    {
        $validated = $this->validate(array_merge([
            'name' => 'required|string|max:150',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_members' => 'required|integer|min:1',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:hospitals,id',
            'terms_conditions' => 'nullable|array',
            'terms_conditions.*.title' => 'nullable|string|max:500',
            'terms_conditions.*.description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ], $this->paymentDiscountValidationRules()));

        $validated['terms_conditions'] = collect($validated['terms_conditions'] ?? [])
            ->map(fn ($row) => [
                'title' => trim((string) ($row['title'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ])
            ->filter(fn ($row) => $row['title'] !== '' || $row['description'] !== '')
            ->values()
            ->all();
        $validated['branch_ids'] = array_values(array_map('intval', $validated['branch_ids'] ?? []));
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $percentages = $validated['payment_discounts'] ?? [];
        unset($validated['payment_discounts']);

        // Lab-test allowances live on plan benefits. Keep legacy column for old records;
        // new packages default to 0 and updates do not overwrite an existing legacy value.
        unset($validated['max_consultations'], $validated['max_hip_coins']);

        if (! $this->packageId) {
            $validated['max_lab_tests'] = 0;
            // 0 HIP coins is display-only and credits nothing. max_consultations is left
            // unset: 0 means unlimited, and the column has no "none" value.
            $validated['max_hip_coins'] = 0;
        }

        if ($this->packageId) {
            $package = $this->familyPackageService->updatePackage($this->packageId, $validated);
            $message = 'Package updated successfully!';
        } else {
            $package = $this->familyPackageService->createPackage($validated);
            $message = 'Package created successfully!';
        }

        $this->savePaymentDiscounts($package, $percentages);

        $this->closeModal();
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function deletePackage(int $id): void
    {
        $this->familyPackageService->deletePackage($id);
        $this->dispatch('toast', type: 'success', message: 'Package deleted successfully!');
    }

    public function closeModal(): void
    {
        $this->resetForm();
        Flux::modal('family-package-form')->close();
    }

    private function resetForm(): void
    {
        $this->reset([
            'packageId', 'name', 'description', 'price', 'duration_days',
            'max_members',
            'branch_ids', 'sort_order',
        ]);
        $this->terms_conditions = [['title' => '', 'description' => '']];
        $this->is_active = true;
        $this->duration_days = 365;
        $this->max_members = 1;
        $this->resetPaymentDiscounts();
        $this->resetErrorBag();
    }
}
