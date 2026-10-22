<?php

namespace App\Livewire\Concerns;

use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\Hospital;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ManagesFamilyPackagePlanBenefits
{
    /** @var list<string> Legacy free-text benefits preserved until structured rows are saved. */
    public array $legacyStringBenefits = [];

    /**
     * @return array{diagnostic_package_id: string, benefit_type: string, max_lab_tests: int}
     */
    protected function emptyBenefitRow(): array
    {
        return [
            'diagnostic_package_id' => '',
            'benefit_type' => 'free',
            'max_lab_tests' => 0,
        ];
    }

    public function addBenefitRow(): void
    {
        $this->benefits[] = $this->emptyBenefitRow();
    }

    public function removeBenefitRow(int $index): void
    {
        unset($this->benefits[$index]);
        $this->benefits = array_values($this->benefits);
    }

    /**
     * @param  list<int|string>|null  $rawBenefits
     */
    protected function loadPlanBenefitsFromPackage(?array $rawBenefits): void
    {
        $rawBenefits = array_values($rawBenefits ?? []);

        if ($rawBenefits === []) {
            $this->benefits = [];
            $this->legacyStringBenefits = [];

            return;
        }

        $structured = [];
        $legacy = [];

        foreach ($rawBenefits as $item) {
            if (FamilyPackage::isPackageBenefit($item)) {
                // Ignore legacy free_consultations in the form; do not bind it to state.
                $structured[] = [
                    'diagnostic_package_id' => (string) ($item['diagnostic_package_id'] ?? ''),
                    'benefit_type' => (string) ($item['benefit_type'] ?? 'free'),
                    'max_lab_tests' => (int) ($item['max_lab_tests'] ?? 0),
                ];
            } elseif (is_string($item) && trim($item) !== '') {
                $legacy[] = trim($item);
            }
        }

        $this->benefits = $structured;
        $this->legacyStringBenefits = $legacy;
    }

    protected function syncBenefitsWithEligiblePackages(?string $feedbackMessage = null): void
    {
        $eligibleIds = $this->eligibleDiagnosticPackageIds($this->branch_ids);
        $cleared = false;

        foreach ($this->benefits as $index => $row) {
            $packageId = (int) ($row['diagnostic_package_id'] ?? 0);

            if ($packageId > 0 && ! in_array($packageId, $eligibleIds, true)) {
                $this->benefits[$index]['diagnostic_package_id'] = '';
                $cleared = true;
            }
        }

        if ($cleared) {
            $this->dispatch(
                'toast',
                type: 'warning',
                message: $feedbackMessage ?? 'Some plan benefits were cleared because their packages are not available for the selected branches.'
            );
        }
    }

    /**
     * @param  list<int|string>  $branchIds
     * @return Collection<int, DiagnosticPackage>
     */
    public function eligibleDiagnosticPackagesForBranches(array $branchIds): Collection
    {
        $branchIds = array_values(array_unique(array_filter(array_map('intval', $branchIds))));

        if ($branchIds === []) {
            return collect();
        }

        $diagnosticIds = Hospital::query()
            ->whereIn('id', $branchIds)
            ->whereNotNull('diagnostic_center_id')
            ->pluck('diagnostic_center_id')
            ->unique()
            ->filter()
            ->values();

        if ($diagnosticIds->isEmpty()) {
            return collect();
        }

        return DiagnosticPackage::query()
            ->active()
            ->whereIn('diagnostic_id', $diagnosticIds)
            ->orderBy('name')
            ->get(['id', 'name', 'diagnostic_id']);
    }

    /**
     * @param  list<int|string>  $branchIds
     * @return list<int>
     */
    public function eligibleDiagnosticPackageIds(array $branchIds): array
    {
        return $this->eligibleDiagnosticPackagesForBranches($branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    /**
     * Drop incomplete benefit rows before validation/save.
     */
    protected function compactBenefitRows(): void
    {
        $this->benefits = collect($this->benefits ?? [])
            ->filter(fn ($row) => filled($row['diagnostic_package_id'] ?? null))
            ->map(fn ($row) => [
                'diagnostic_package_id' => (string) (int) $row['diagnostic_package_id'],
                'benefit_type' => (string) ($row['benefit_type'] ?? 'free'),
                'max_lab_tests' => $row['max_lab_tests'] ?? 0,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function planBenefitValidationRules(): array
    {
        $eligibleIds = $this->eligibleDiagnosticPackageIds($this->branch_ids);

        return [
            'benefits' => 'nullable|array',
            'benefits.*.diagnostic_package_id' => [
                'required',
                'integer',
                'distinct',
                Rule::in($eligibleIds),
            ],
            'benefits.*.benefit_type' => 'required|in:free',
            'benefits.*.max_lab_tests' => 'required|integer|min:0',
        ];
    }

    /**
     * Persist structured package benefits, or preserve legacy string benefits when no rows are configured.
     *
     * @return list<array{diagnostic_package_id: int, benefit_type: string, max_lab_tests: int}|string>
     */
    protected function prepareBenefitsForSave(): array
    {
        $eligibleIds = $this->eligibleDiagnosticPackageIds($this->branch_ids);

        $rows = collect($this->benefits ?? [])
            ->map(function (array $row) {
                return [
                    'diagnostic_package_id' => (int) ($row['diagnostic_package_id'] ?? 0),
                    'benefit_type' => (string) ($row['benefit_type'] ?? 'free'),
                    'max_lab_tests' => max(0, (int) ($row['max_lab_tests'] ?? 0)),
                ];
            })
            ->filter(fn (array $row) => $row['diagnostic_package_id'] > 0)
            ->values();

        if ($rows->isEmpty()) {
            return array_values($this->legacyStringBenefits);
        }

        foreach ($rows as $index => $row) {
            if ($row['benefit_type'] !== 'free') {
                throw ValidationException::withMessages([
                    "benefits.{$index}.benefit_type" => 'Benefit type must be free.',
                ]);
            }

            if (! in_array($row['diagnostic_package_id'], $eligibleIds, true)) {
                throw ValidationException::withMessages([
                    "benefits.{$index}.diagnostic_package_id" => 'Selected package is not available for the chosen branches.',
                ]);
            }
        }

        $this->legacyStringBenefits = [];

        return $rows->all();
    }
}
