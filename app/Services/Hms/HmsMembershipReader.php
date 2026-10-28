<?php

namespace App\Services\Hms;

use App\Models\DiagnosticPackage;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use App\Services\MembershipEntitlementService;

/** Read-only membership view. Does not reserve usage or expire subscriptions. */
class HmsMembershipReader
{
    public function __construct(
        private readonly MembershipEntitlementService $membership,
        private readonly FamilyPackageService $familyPackages,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forPatient(HIPUser $account, ?Persons $person, int $hospitalId): array
    {
        $eligibility = $this->membership->evaluateConsultationEligibility(
            (string) $account->getKey(),
            $hospitalId,
            true
        );

        /** @var UserFamilySubscription|null $subscription */
        $subscription = $eligibility['subscription'] ?? null;
        /** @var FamilyPackage|null $package */
        $package = $eligibility['package'] ?? $subscription?->familyPackage;

        if (! $subscription || ! $package) {
            return $this->emptyMembership();
        }

        $branchIds = array_map('intval', $package->branch_ids ?? []);
        $consultationLimit = (int) ($package->max_consultations ?? 0);
        $consultationsUsed = $subscription->usageLogs()
            ->where('usage_type', 'consultation')
            ->count();

        return [
            'has_membership' => true,
            'valid_at_hospital' => ($eligibility['reason'] ?? null) !== 'branch_not_allowed',
            'patient_covered' => $this->patientCovered($subscription, $person),
            'plan' => [
                'id' => (int) $package->id,
                'code' => $package->code,
                'name' => $package->name,
                'payment_status' => $subscription->payment_status,
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
                'days_left' => $this->daysLeft($subscription),
                'branches' => array_values($branchIds),
            ],
            'free_consultations' => $this->allowance($consultationLimit, $consultationsUsed),
            'free_packages' => $this->freePackages($package, $subscription),
            'covered_members' => $this->coveredMembers($subscription),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyMembership(): array
    {
        return [
            'has_membership' => false,
            'valid_at_hospital' => false,
            'patient_covered' => false,
            'plan' => null,
            'free_consultations' => null,
            'free_packages' => [],
            'covered_members' => [],
        ];
    }

    private function patientCovered(UserFamilySubscription $subscription, ?Persons $person): bool
    {
        $coveredIds = collect($subscription->covered_member_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();

        if ($coveredIds === []) {
            return true;
        }

        return $person !== null && in_array((string) $person->getKey(), $coveredIds, true);
    }

    /**
     * @return list<array{person_id: string, name: string, relationship: string}>
     */
    private function coveredMembers(UserFamilySubscription $subscription): array
    {
        return array_map(function (array $member): array {
            return [
                'person_id' => (string) $member['id'],
                'name' => (string) ($member['name'] ?? ''),
                'relationship' => (string) ($member['relationship'] ?? ''),
            ];
        }, $this->familyPackages->resolveCoveredMembersDetails($subscription));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function freePackages(FamilyPackage $package, UserFamilySubscription $subscription): array
    {
        $benefits = array_values(array_filter(
            $package->structuredPackageBenefits(),
            fn (array $benefit) => ($benefit['benefit_type'] ?? '') === 'free'
        ));

        if ($benefits === []) {
            return [];
        }

        $ids = array_map(fn (array $benefit) => (int) $benefit['diagnostic_package_id'], $benefits);
        $catalog = DiagnosticPackage::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return array_map(function (array $benefit) use ($subscription, $catalog): array {
            $packageId = (int) $benefit['diagnostic_package_id'];
            $limit = (int) ($benefit['max_lab_tests'] ?? 0);
            $used = $this->familyPackages->countLabTestUsageForDiagnosticPackage($subscription, $packageId);
            $row = $catalog->get($packageId);
            $allowance = $this->allowance($limit, $used);

            return [
                'package_id' => $packageId,
                'package_code' => $row?->code,
                'name' => $row?->name,
                'unlimited' => $allowance['unlimited'],
                'total' => $allowance['total'],
                'used' => $allowance['used'],
                'remaining' => $allowance['remaining'],
            ];
        }, $benefits);
    }

    /**
     * @return array{unlimited: bool, total: int, used: int, remaining: int|null}
     */
    private function allowance(int $limit, int $used): array
    {
        return [
            'unlimited' => $limit === 0,
            'total' => $limit,
            'used' => $used,
            'remaining' => $limit === 0 ? null : max(0, $limit - $used),
        ];
    }

    private function daysLeft(UserFamilySubscription $subscription): int
    {
        if (! $subscription->end_date) {
            return 0;
        }

        $today = now()->startOfDay();
        $end = $subscription->end_date->copy()->startOfDay();
        if ($end->lt($today)) {
            return 0;
        }

        return (int) round((float) $today->diffInDays($end));
    }
}
