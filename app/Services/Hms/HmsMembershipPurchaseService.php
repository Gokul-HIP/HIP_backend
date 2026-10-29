<?php

namespace App\Services\Hms;

use App\Exceptions\HmsClientException;
use App\Models\FamilyPackage;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\HmsMembership;
use App\Models\Hospital;
use App\Models\Transactions;
use App\Models\UserFamilySubscription;
use App\Services\FamilyPackageService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/** Activates an HMS membership through the cashier purchase path. */
class HmsMembershipPurchaseService
{
    public function __construct(
        private readonly HmsHospitalResolver $hospitals,
        private readonly HmsPatientProvisioner $patients,
        private readonly FamilyPackageService $familyPackages,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{http_status: int, body: array<string, mixed>}
     */
    public function purchase(HmsIntegration $integration, array $payload): array
    {
        $input = $this->validated($payload);
        $hash = HmsPayloadHash::make($payload);

        try {
            $result = DB::transaction(function () use ($integration, $input, $hash) {
                HmsIntegration::query()->whereKey($integration->getKey())->lockForUpdate()->first();
                $this->guardPaymentReference($integration, $input['hms_membership_id'], $input['reference']);
                $existing = $this->locked($integration, $input['hms_membership_id']);
                if ($existing) {
                    return $this->replay($existing, $hash);
                }

                return $this->activate($integration, $input, $hash);
            });
        } catch (QueryException $exception) {
            if (! $this->isUniqueViolation($exception)) {
                throw $exception;
            }
            $existing = $this->find($integration, $input['hms_membership_id']);
            $byReference = HmsMembership::query()
                ->where('integration_id', $integration->getKey())
                ->where('payment_reference', $input['reference'])
                ->first();
            if ($byReference && (! $existing || (int) $byReference->id !== (int) $existing->id)) {
                throw new HmsClientException('payment_reference_used', 'This payment reference was already used.', 409);
            }
            if (! $existing) {
                throw $exception;
            }
            $result = $this->replay($existing, $hash);
        }

        return [
            'http_status' => $result['created'] ? 201 : 200,
            'body' => $this->present($result['row']),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array{row: HmsMembership, created: bool}
     */
    private function activate(HmsIntegration $integration, array $input, string $hash): array
    {
        $hospital = $this->hospitals->resolve($integration, $input['hospital_code']);
        $plan = $this->plan($integration, $input['plan_code'], $hospital);
        $purchaser = $this->patients->provision($integration, $input['patient'], (int) $hospital->getKey());
        $hipUserId = (string) $purchaser['account']->getKey();
        $purchaserPersonId = (string) $purchaser['person']->getKey();
        $active = $this->familyPackages->getUserActiveSubscription($hipUserId);
        $samePlan = $active && (int) $active->family_package_id === (int) $plan->id;

        if ($input['type'] === 'new' && $active) {
            throw new HmsClientException('membership_already_active', 'This patient already has an active membership.', 409);
        }
        if ($input['type'] === 'renewal' && $active && ! $samePlan) {
            throw new HmsClientException('membership_already_active', 'This patient already has an active membership for a different plan.', 409);
        }

        if ($samePlan && $active) {
            $this->mergeCovered($integration, $active, $input, (int) $hospital->getKey(), $plan, $purchaserPersonId);
            $active->end_date = $active->end_date->copy()->addDays((int) $plan->duration_days);
            $active->save();
            $subscription = $active;
        } else {
            $this->guardStartDate($input);
            $coveredIds = $this->coveredIds($integration, $hipUserId, $input, (int) $hospital->getKey(), $plan, $purchaserPersonId);
            $mode = $input['method'] === 'online' ? 'online' : 'cash';
            $subscription = $this->familyPackages->createCashierSubscription(
                $hipUserId,
                (int) $plan->id,
                $mode,
                $input['amount'],
                null,
                $coveredIds,
            );
            $start = $this->startDate($input);
            $subscription->start_date = $start->toDateString();
            $subscription->end_date = $start->copy()->addDays((int) $plan->duration_days)->toDateString();
            $subscription->save();
            if ($input['method'] !== '' && $input['method'] !== $mode) {
                $subscription->invoice?->update(['payment_method' => $input['method']]);
                Transactions::query()->where('invoice_id', $subscription->invoice_id)->update(['payment_method' => $input['method']]);
            }
        }

        $expected = round((float) $plan->price, 2);
        $row = HmsMembership::query()->create([
            'integration_id' => $integration->getKey(),
            'hms_membership_id' => $input['hms_membership_id'],
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'hip_user_id' => $hipUserId,
            'hospital_id' => $hospital->getKey(),
            'type' => $input['type'],
            'amount_paid' => $input['amount'],
            'expected_amount' => $expected,
            'price_mismatch' => abs($input['amount'] - $expected) > 0.01,
            'payment_reference' => $input['reference'],
            'request_hash' => $hash,
        ]);

        return ['row' => $row, 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return list<string>
     */
    private function coveredIds(HmsIntegration $integration, string $hipUserId, array $input, int $hospitalId, FamilyPackage $plan, string $purchaserPersonId): array
    {
        $members = $input['covered_members'];
        if ($members === []) {
            $ids = array_column($this->familyPackages->getFamilyMembersForUser($hipUserId), 'id');
        } else {
            $ids = [$purchaserPersonId];
            foreach ($members as $member) {
                $ids[] = $this->provisionCovered($integration, $input, $member, $hospitalId);
            }
        }

        return $this->limitMembers($plan, $ids, $purchaserPersonId);
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function mergeCovered(HmsIntegration $integration, UserFamilySubscription $subscription, array $input, int $hospitalId, FamilyPackage $plan, string $purchaserPersonId): void
    {
        if ($input['covered_members'] === []) {
            return;
        }

        $existing = array_map('strval', $subscription->covered_member_ids ?? []);
        if ($existing === []) {
            $existing = array_column($this->familyPackages->getFamilyMembersForUser((string) $subscription->hip_user_id), 'id');
        }
        $ids = $existing;
        foreach ($input['covered_members'] as $member) {
            $ids[] = $this->provisionCovered($integration, $input, $member, $hospitalId);
        }
        $subscription->covered_member_ids = $this->limitMembers($plan, $ids, $purchaserPersonId);
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $member
     */
    private function provisionCovered(HmsIntegration $integration, array $input, array $member, int $hospitalId): string
    {
        $person = $this->patients->provision($integration, [
            'mobile' => (string) ($member['mobile'] ?? ''),
            'name' => (string) ($member['name'] ?? ''),
            'dob' => $member['dob'] ?? null,
            'gender' => $member['gender'] ?? null,
            'email' => $member['email'] ?? null,
            'patient_ref' => (string) ($member['patient_ref'] ?? ''),
            'relationship' => (string) ($member['relationship'] ?? 'Child'),
            'primary_mobile' => $input['patient']['mobile'],
        ], $hospitalId);

        return (string) $person['person']->getKey();
    }

    /**
     * @param  list<mixed>  $ids
     * @return list<string>
     */
    private function limitMembers(FamilyPackage $plan, array $ids, string $purchaserPersonId): array
    {
        $ids = array_values(array_unique(array_merge(
            [$purchaserPersonId],
            array_map('strval', $ids)
        )));
        $max = max(1, (int) $plan->max_members);
        if (count($ids) > $max) {
            throw new HmsClientException('too_many_members', 'This plan covers at most '.$max.' members, including the purchaser.', 422);
        }

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function guardStartDate(array $input): void
    {
        $start = $input['start_date'];
        if ($start instanceof Carbon && $start->toDateString() > Carbon::today()->toDateString()) {
            throw new HmsClientException('start_date_in_future', 'start_date cannot be in the future.', 422);
        }
    }

    private function plan(HmsIntegration $integration, string $planCode, Hospital $hospital): FamilyPackage
    {
        $mapped = HmsExternalIdMap::query()
            ->where('integration_id', $integration->getKey())
            ->where('entity_type', HmsExternalIdMap::TYPE_PLAN)
            ->where('external_id', $planCode)
            ->value('local_id');
        $plan = $mapped
            ? FamilyPackage::active()->find($mapped)
            : FamilyPackage::active()->where('code', $planCode)->first();
        if (! $plan) {
            throw new HmsClientException('not_found', 'Plan was not found.', 404);
        }

        $branchIds = array_map('intval', $plan->branch_ids ?? []);
        if ($branchIds !== [] && ! in_array((int) $hospital->getKey(), $branchIds, true)) {
            throw new HmsClientException('not_found', 'Plan was not found.', 404);
        }

        return $plan;
    }

    /**
     * @param  array<string, mixed>  $input
     */
    private function startDate(array $input): Carbon
    {
        if ($input['start_date'] instanceof Carbon) {
            return $input['start_date']->copy()->startOfDay();
        }
        if ($input['paid_at'] instanceof Carbon) {
            return $input['paid_at']->copy()->startOfDay();
        }

        return Carbon::today();
    }

    private function guardPaymentReference(HmsIntegration $integration, string $hmsMembershipId, string $reference): void
    {
        $used = HmsMembership::query()
            ->where('integration_id', $integration->getKey())
            ->where('payment_reference', $reference)
            ->lockForUpdate()
            ->first();
        if ($used && $used->hms_membership_id !== $hmsMembershipId) {
            throw new HmsClientException('payment_reference_used', 'This payment reference was already used.', 409);
        }
    }

    /**
     * @return array{row: HmsMembership, created: bool}
     */
    private function replay(HmsMembership $existing, string $hash): array
    {
        if (! hash_equals($existing->request_hash, $hash)) {
            throw new HmsClientException(
                'membership_conflict',
                'This hms_membership_id was already purchased with a different payload.',
                409,
                ['request_hash' => $existing->request_hash]
            );
        }

        return ['row' => $existing, 'created' => false];
    }

    /**
     * @return array<string, mixed>
     */
    private function present(HmsMembership $row): array
    {
        $subscription = UserFamilySubscription::query()->with('familyPackage')->findOrFail($row->subscription_id);
        $plan = $subscription->familyPackage;

        return [
            'status' => true,
            'membership' => [
                'subscription_id' => (int) $subscription->id,
                'plan' => [
                    'id' => (int) $plan->id,
                    'code' => $plan->code,
                    'name' => $plan->name,
                ],
                'start_date' => $subscription->start_date?->toDateString(),
                'end_date' => $subscription->end_date?->toDateString(),
                'covered_members' => array_map(function (array $member): array {
                    return [
                        'person_id' => (string) $member['id'],
                        'name' => (string) ($member['name'] ?? ''),
                        'relationship' => (string) ($member['relationship'] ?? ''),
                    ];
                }, $this->familyPackages->resolveCoveredMembersDetails($subscription)),
            ],
            'price_mismatch' => (bool) $row->price_mismatch,
            'expected_amount' => round((float) $row->expected_amount, 2),
            'received_amount' => round((float) $row->amount_paid, 2),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function validated(array $payload): array
    {
        $hmsId = trim((string) ($payload['hms_membership_id'] ?? ''));
        $type = strtolower(trim((string) ($payload['type'] ?? '')));
        $planCode = trim((string) ($payload['plan_code'] ?? ''));
        $hospitalCode = trim((string) ($payload['hospital_code'] ?? ''));
        $patient = $payload['patient'] ?? null;
        $payment = (array) ($payload['payment'] ?? []);
        $reference = trim((string) ($payment['reference'] ?? ''));
        $members = $payload['covered_members'] ?? [];

        if ($hmsId === '' || $planCode === '' || $hospitalCode === '' || $reference === '' || ! is_array($patient)) {
            throw new HmsClientException('validation_error', 'hms_membership_id, plan_code, hospital_code, patient, and payment.reference are required.', 422);
        }
        if (! in_array($type, ['new', 'renewal'], true)) {
            throw new HmsClientException('validation_error', 'type must be new or renewal.', 422);
        }
        if (trim((string) ($patient['mobile'] ?? '')) === '' || trim((string) ($patient['name'] ?? '')) === '') {
            throw new HmsClientException('validation_error', 'patient.mobile and patient.name are required.', 422);
        }
        if (! is_numeric($payload['amount_paid'] ?? null) || (float) $payload['amount_paid'] < 0) {
            throw new HmsClientException('validation_error', 'amount_paid must be a non-negative number of rupees.', 422);
        }
        if (! is_array($members)) {
            throw new HmsClientException('validation_error', 'covered_members must be a list.', 422);
        }
        foreach ($members as $member) {
            if (! is_array($member) || trim((string) ($member['patient_ref'] ?? '')) === '' || trim((string) ($member['name'] ?? '')) === '') {
                throw new HmsClientException('validation_error', 'Each covered member needs patient_ref and name.', 422);
            }
            if (strcasecmp(trim((string) ($member['relationship'] ?? 'Child')), 'Self') === 0) {
                throw new HmsClientException('validation_error', 'A covered member uses a relationship other than Self.', 422);
            }
        }

        $paidAt = null;
        if (filled($payment['paid_at'] ?? null)) {
            try {
                $paidAt = Carbon::parse((string) $payment['paid_at']);
            } catch (\Throwable) {
                throw new HmsClientException('validation_error', 'payment.paid_at must be a date time.', 422);
            }
        }
        $start = null;
        if (filled($payload['start_date'] ?? null)) {
            try {
                $start = Carbon::parse((string) $payload['start_date'])->startOfDay();
            } catch (\Throwable) {
                throw new HmsClientException('validation_error', 'start_date must be a date.', 422);
            }
        }

        $patient['mobile'] = HmsMobile::normalize((string) $patient['mobile']);
        $patient['relationship'] = 'Self';

        return [
            'hms_membership_id' => $hmsId,
            'type' => $type,
            'plan_code' => $planCode,
            'hospital_code' => $hospitalCode,
            'patient' => $patient,
            'covered_members' => array_values($members),
            'amount' => round((float) $payload['amount_paid'], 2),
            'reference' => $reference,
            'method' => strtolower(trim((string) ($payment['method'] ?? ''))),
            'paid_at' => $paidAt,
            'start_date' => $start,
        ];
    }

    private function locked(HmsIntegration $integration, string $hmsMembershipId): ?HmsMembership
    {
        return HmsMembership::query()
            ->where('integration_id', $integration->getKey())
            ->where('hms_membership_id', $hmsMembershipId)
            ->lockForUpdate()
            ->first();
    }

    private function find(HmsIntegration $integration, string $hmsMembershipId): ?HmsMembership
    {
        return HmsMembership::query()
            ->where('integration_id', $integration->getKey())
            ->where('hms_membership_id', $hmsMembershipId)
            ->first();
    }

    private function isUniqueViolation(QueryException $exception): bool
    {
        $state = (string) ($exception->errorInfo[0] ?? $exception->getCode());

        return $state === '23000' || str_contains(strtolower($exception->getMessage()), 'unique');
    }
}
