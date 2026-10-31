<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class FamilyPackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration_days',
        'max_members',
        'max_consultations',
        'max_lab_tests',
        'max_hip_coins',
        'branch_ids',
        'benefits',
        'terms_conditions',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'branch_ids' => 'array',
        'benefits' => 'array',
        'terms_conditions' => 'array',
        'is_active' => 'boolean',
        'duration_days' => 'integer',
        'max_members' => 'integer',
        'max_consultations' => 'integer',
        'max_lab_tests' => 'integer',
        'max_hip_coins' => 'integer',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserFamilySubscription::class, 'family_package_id');
    }

    public function paymentDiscounts(): HasMany
    {
        return $this->hasMany(MembershipPackageDiscount::class, 'family_package_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public static function isPackageBenefit(mixed $benefit): bool
    {
        return is_array($benefit) && array_key_exists('diagnostic_package_id', $benefit);
    }

    public static function formatBenefitLabel(mixed $benefit, ?string $packageName = null): ?string
    {
        if (is_string($benefit)) {
            $trimmed = trim($benefit);

            return $trimmed !== '' ? $trimmed : null;
        }

        if (! self::isPackageBenefit($benefit)) {
            return null;
        }

        $name = $packageName
            ?: ('Package #'.(int) ($benefit['diagnostic_package_id'] ?? 0));
        $labTests = (int) ($benefit['max_lab_tests'] ?? 0);
        $labLabel = $labTests === 0 ? 'unlimited lab tests' : ($labTests === 1 ? '1 lab test' : "{$labTests} lab tests");

        return "{$name}: {$labLabel}";
    }

    /**
     * Structured diagnostic-package benefit rows only.
     *
     * @return list<array{diagnostic_package_id: int, benefit_type: string, max_lab_tests: int}>
     */
    public function structuredPackageBenefits(): array
    {
        return collect($this->benefits ?? [])
            ->filter(fn ($item) => self::isPackageBenefit($item))
            ->map(fn (array $item) => [
                'diagnostic_package_id' => (int) ($item['diagnostic_package_id'] ?? 0),
                'benefit_type' => (string) ($item['benefit_type'] ?? 'free'),
                'max_lab_tests' => (int) ($item['max_lab_tests'] ?? 0),
            ])
            ->filter(fn (array $item) => $item['diagnostic_package_id'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array{diagnostic_package_id: int, benefit_type: string, max_lab_tests: int}|null
     */
    public function benefitForDiagnosticPackage(int $diagnosticPackageId): ?array
    {
        foreach ($this->structuredPackageBenefits() as $benefit) {
            if ($benefit['diagnostic_package_id'] === $diagnosticPackageId) {
                return $benefit;
            }
        }

        return null;
    }

    /**
     * Card/list label for lab-test allowance (benefit-level when configured, else legacy package field).
     */
    public function labTestAllowanceLabel(): string
    {
        $benefits = $this->structuredPackageBenefits();

        if ($benefits !== []) {
            $parts = collect($benefits)->map(function (array $benefit) {
                $max = (int) ($benefit['max_lab_tests'] ?? 0);

                return $max === 0 ? 'Unlimited' : (string) $max;
            })->unique()->values();

            if ($parts->count() === 1) {
                $value = $parts->first();

                return $value === 'Unlimited' ? 'Unlimited' : $value;
            }

            return 'Per package benefit';
        }

        $legacy = (int) ($this->max_lab_tests ?? 0);

        return $legacy === 0 ? 'Unlimited' : (string) $legacy;
    }

    /**
     * Human-readable benefit lines for admin cards and legacy string consumers.
     *
     * @return list<string>
     */
    public function benefitDisplayLines(?Collection $packageNamesById = null): array
    {
        $benefits = $this->benefits ?? [];
        $names = $packageNamesById;

        if ($names === null) {
            $packageIds = collect($benefits)
                ->filter(fn ($item) => self::isPackageBenefit($item))
                ->pluck('diagnostic_package_id')
                ->filter()
                ->unique()
                ->values();

            $names = $packageIds->isEmpty()
                ? collect()
                : DiagnosticPackage::query()->whereIn('id', $packageIds)->pluck('name', 'id');
        }

        return collect($benefits)
            ->map(function ($benefit) use ($names) {
                if (self::isPackageBenefit($benefit)) {
                    $packageId = (int) ($benefit['diagnostic_package_id'] ?? 0);

                    return self::formatBenefitLabel($benefit, $names[$packageId] ?? null);
                }

                return self::formatBenefitLabel($benefit);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * API-ready benefits: legacy strings unchanged; structured rows enriched with package_name + label.
     *
     * @return list<string|array{diagnostic_package_id: int, benefit_type: string, max_lab_tests: int, package_name: string|null, label: string}>
     */
    public function benefitsForApi(?Collection $packageNamesById = null): array
    {
        $benefits = $this->benefits ?? [];
        $names = $packageNamesById;

        if ($names === null) {
            $packageIds = collect($benefits)
                ->filter(fn ($item) => self::isPackageBenefit($item))
                ->pluck('diagnostic_package_id')
                ->filter()
                ->unique()
                ->values();

            $names = $packageIds->isEmpty()
                ? collect()
                : DiagnosticPackage::query()->whereIn('id', $packageIds)->pluck('name', 'id');
        }

        return collect($benefits)
            ->map(function ($benefit) use ($names) {
                if (is_string($benefit)) {
                    $trimmed = trim($benefit);

                    return $trimmed !== '' ? $trimmed : null;
                }

                if (! self::isPackageBenefit($benefit)) {
                    return null;
                }

                $packageId = (int) ($benefit['diagnostic_package_id'] ?? 0);
                $packageName = $names[$packageId] ?? null;
                $row = [
                    'diagnostic_package_id' => $packageId,
                    'benefit_type' => (string) ($benefit['benefit_type'] ?? 'free'),
                    'max_lab_tests' => (int) ($benefit['max_lab_tests'] ?? 0),
                    'package_name' => $packageName,
                    'label' => self::formatBenefitLabel($benefit, $packageName),
                ];

                // Preserve legacy free_consultations on read-only API payloads when still stored.
                if (array_key_exists('free_consultations', $benefit)) {
                    $row['free_consultations'] = (int) $benefit['free_consultations'];
                }

                return $row;
            })
            ->filter()
            ->values()
            ->all();
    }
}
