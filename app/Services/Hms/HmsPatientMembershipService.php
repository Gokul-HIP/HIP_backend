<?php

namespace App\Services\Hms;

use App\Enums\MembershipPaymentType;
use App\Models\Coins;
use App\Models\FamilyPackage;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\UserFamilySubscription;
use App\Services\Api\PaymentApiService;
use App\Services\MembershipEntitlementService;

/** Read-only membership and coin snapshot for one person. */
class HmsPatientMembershipService
{
    public function __construct(
        private readonly MembershipEntitlementService $membership,
        private readonly PaymentApiService $payments,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function lookup(string $mrnId, ?string $mobile): ?array
    {
        $person = $this->findPerson($mrnId, $mobile);

        if (! $person) {
            return null;
        }

        return $this->payload($person);
    }

    private function findPerson(string $mrnId, ?string $mobile): ?Persons
    {
        $mrn = trim($mrnId);
        if ($mrn !== '') {
            $person = Persons::query()->where('mrn', $mrn)->first();
            if ($person) {
                return $person;
            }
        }

        $normalized = HmsMobile::normalize((string) $mobile);
        if ($normalized === '') {
            return null;
        }

        $user = HIPUser::query()->where('mobile_num', $normalized)->first();
        if (! $user) {
            return null;
        }

        return Persons::query()
            ->where('hip_user_id', $user->getKey())
            ->orderByDesc('is_primary')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Persons $person): array
    {
        $accountId = $this->accountId($person);
        $subscription = null;
        $package = null;

        if ($accountId !== null) {
            $eligibility = $this->membership->evaluateConsultationEligibility($accountId, null, true);
            $subscription = $eligibility['subscription'] ?? null;
            $package = $eligibility['package'] ?? $subscription?->familyPackage;
        }

        $active = $subscription instanceof UserFamilySubscription && $package instanceof FamilyPackage;

        $body = [
            'status' => true,
            'mrn_id' => $person->mrn,
            'has_membership' => $active,
            'membership_id' => $active ? (int) $subscription->id : null,
            'plan_name' => $active ? (string) $package->name : null,
            'valid_from' => $active ? $subscription->start_date?->toDateString() : null,
            'valid_until' => $active ? $subscription->end_date?->toDateString() : null,
            'covered_member' => $active && $this->personIsCovered($subscription, $person),
            'coins' => [
                'balance' => $this->coinBalance($person),
                'value_per_coin' => $this->payments->amountForOneCoin(),
            ],
        ];

        if ($active) {
            $discounts = $this->discounts($package);
            $body = [
                'status' => true,
                'mrn_id' => $person->mrn,
                'has_membership' => true,
                'membership_id' => (int) $subscription->id,
                'plan_name' => (string) $package->name,
                'valid_from' => $subscription->start_date?->toDateString(),
                'valid_until' => $subscription->end_date?->toDateString(),
                'covered_member' => $this->personIsCovered($subscription, $person),
                'discounts' => $discounts,
                'coins' => $body['coins'],
            ];
        }

        return $body;
    }

    private function accountId(Persons $person): ?string
    {
        if (filled($person->hip_user_id)) {
            return (string) $person->hip_user_id;
        }

        if (filled($person->parent_id)) {
            $parent = Persons::query()->find($person->parent_id);
            if ($parent && filled($parent->hip_user_id)) {
                return (string) $parent->hip_user_id;
            }
        }

        return null;
    }

    private function personIsCovered(UserFamilySubscription $subscription, Persons $person): bool
    {
        $coveredIds = collect($subscription->covered_member_ids ?? [])
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->values()
            ->all();

        if ($coveredIds === []) {
            return true;
        }

        return in_array((string) $person->getKey(), $coveredIds, true);
    }

    /**
     * @return array<string, array{percentage: float}>
     */
    private function discounts(FamilyPackage $package): array
    {
        $saved = $package->paymentDiscounts()->get()->keyBy('payment_type');
        $discounts = [];

        foreach (MembershipPaymentType::values() as $type) {
            if (! $saved->has($type)) {
                continue;
            }

            $discounts[$type] = [
                'percentage' => (float) $saved[$type]->percentage,
            ];
        }

        return $discounts;
    }

    /**
     * Same wallet the mobile coins page reads: the person's parent row, or the person.
     */
    private function coinBalance(Persons $person): int
    {
        $walletPersonId = (string) ($person->parent_id ?: $person->id);
        $wallet = Coins::query()->where('person_id', $walletPersonId)->latest('id')->first();

        return (int) ($wallet?->coins ?? 0);
    }
}
