<?php

namespace App\Livewire\HospitalAdmin\Settings;

use App\Livewire\Concerns\ManagesFamilyPackagePlanBenefits;
use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\Hospital;
use App\Services\FamilyPackageService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManageMembershipPackages extends Component
{
    use ManagesFamilyPackagePlanBenefits;

    public $packageId;

    public $name = '';

    public $code = '';

    public $description = '';

    public $price = '';

    public $duration_days = 365;

    public $max_members = 1;

    public $max_consultations = 0;

    public $max_hip_coins = 0;

    public array $branch_ids = [];

    public array $benefits = [];

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
        $organizationId = Auth::guard('filament')->user()?->organization_id;
        $organizationHospitalIds = $this->organizationHospitalIds();

        $packages = FamilyPackage::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->when($organizationHospitalIds !== [], function ($collection) use ($organizationHospitalIds) {
                return $collection->filter(function (FamilyPackage $package) use ($organizationHospitalIds) {
                    $branchIds = $package->branch_ids ?? [];

                    if ($branchIds === []) {
                        return true;
                    }

                    return collect($branchIds)->intersect($organizationHospitalIds)->isNotEmpty();
                })->values();
            });

        $hospitals = Hospital::query()
            ->where('status', 'active')
            ->when($organizationId, fn ($query) => $query->where('organization_id', $organizationId))
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

        $eligiblePackages = $this->eligibleDiagnosticPackagesForBranches($this->branch_ids);

        return view('livewire.hospital-admin.settings.manage-membership-packages', [
            'packages' => $packages,
            'hospitals' => $hospitals,
            'branchNames' => $branchNames,
            'benefitPackageNames' => $benefitPackageNames,
            'eligiblePackages' => $eligiblePackages,
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
        $this->code = $package->code ?? '';
        $this->description = $package->description ?? '';
        $this->price = $package->price;
        $this->duration_days = $package->duration_days;
        $this->max_members = $package->max_members;
        $this->max_consultations = $package->max_consultations;
        $this->max_hip_coins = $package->max_hip_coins;
        $this->branch_ids = array_values(array_intersect(
            array_map('intval', $package->branch_ids ?? []),
            $this->organizationHospitalIds()
        ));
        $this->loadPlanBenefitsFromPackage($package->benefits);
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
        $organizationHospitalIds = $this->organizationHospitalIds();

        if ($organizationHospitalIds !== [] && ! in_array($branchId, $organizationHospitalIds, true)) {
            return;
        }

        if (in_array($branchId, $this->branch_ids, true)) {
            $this->branch_ids = array_values(array_diff($this->branch_ids, [$branchId]));
        } else {
            $this->branch_ids[] = $branchId;
        }

        $this->syncBenefitsWithEligiblePackages();
    }

    public function savePackage(): void
    {
        $organizationHospitalIds = $this->organizationHospitalIds();
        $this->compactBenefitRows();
        $this->code = trim((string) ($this->code ?? ''));
        if ($this->code === '') {
            $this->code = null;
        }

        $validated = $this->validate(array_merge([
            'name' => 'required|string|max:150',
            'code' => ['nullable', 'string', 'max:64', Rule::unique('family_packages', 'code')->ignore($this->packageId)],
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_members' => 'required|integer|min:1',
            'max_consultations' => 'required|integer|min:0',
            'max_hip_coins' => 'required|integer|min:0',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'integer|exists:hospitals,id',
            'terms_conditions' => 'nullable|array',
            'terms_conditions.*.title' => 'nullable|string|max:500',
            'terms_conditions.*.description' => 'nullable|string|max:2000',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ], $this->planBenefitValidationRules()), [
            'code.unique' => 'This plan code is already in use.',
        ]);

        $validated['code'] = filled($validated['code'] ?? null) ? $validated['code'] : null;
        $validated['benefits'] = $this->prepareBenefitsForSave();
        $validated['terms_conditions'] = collect($validated['terms_conditions'] ?? [])
            ->map(fn ($row) => [
                'title' => trim((string) ($row['title'] ?? '')),
                'description' => trim((string) ($row['description'] ?? '')),
            ])
            ->filter(fn ($row) => $row['title'] !== '' || $row['description'] !== '')
            ->values()
            ->all();
        $validated['branch_ids'] = array_values(array_intersect(
            array_map('intval', $validated['branch_ids'] ?? []),
            $organizationHospitalIds
        ));
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        // Lab-test allowances live on plan benefits. Keep legacy column for old records;
        // new packages default to 0 and updates do not overwrite an existing legacy value.
        if (! $this->packageId) {
            $validated['max_lab_tests'] = 0;
        }

        if ($this->packageId) {
            $this->familyPackageService->updatePackage($this->packageId, $validated);
            $message = 'Package updated successfully!';
        } else {
            $this->familyPackageService->createPackage($validated);
            $message = 'Package created successfully!';
        }

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
            'packageId', 'name', 'code', 'description', 'price', 'duration_days',
            'max_members', 'max_consultations', 'max_hip_coins',
            'branch_ids', 'sort_order', 'legacyStringBenefits',
        ]);
        $this->benefits = [];
        $this->legacyStringBenefits = [];
        $this->terms_conditions = [['title' => '', 'description' => '']];
        $this->is_active = true;
        $this->duration_days = 365;
        $this->max_members = 1;
        $this->resetErrorBag();
    }

    /**
     * @return list<int>
     */
    private function organizationHospitalIds(): array
    {
        $organizationId = Auth::guard('filament')->user()?->organization_id;

        if (! $organizationId) {
            return [];
        }

        return Hospital::query()
            ->where('organization_id', $organizationId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
