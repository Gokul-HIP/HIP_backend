<?php

namespace App\Services\Hms;

use App\Models\HIPUser;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\Persons;

/** Read-only. Never creates HIP accounts or person rows. */
class HmsPatientResolver
{
    /**
     * @return array{account: HIPUser, person: Persons|null}|null
     */
    public function resolve(HmsIntegration $integration, string $mobile, ?string $patientRef): ?array
    {
        $account = $this->findAccount($mobile);
        if (! $account) {
            return null;
        }

        if ($patientRef !== null && trim($patientRef) !== '') {
            $mapped = HmsExternalIdMap::query()
                ->where('integration_id', $integration->getKey())
                ->where('entity_type', HmsExternalIdMap::TYPE_PATIENT)
                ->where('external_id', trim($patientRef))
                ->first();

            if (! $mapped) {
                return null;
            }

            $person = Persons::query()->find($mapped->local_id);
            if (! $person) {
                return null;
            }

            $owner = $this->owningAccount($person);
            if (! $owner || (string) $owner->getKey() !== (string) $account->getKey()) {
                return null;
            }

            return [
                'account' => $account,
                'person' => $person,
            ];
        }

        $person = Persons::query()
            ->where('hip_user_id', $account->getKey())
            ->orderByDesc('is_primary')
            ->first();

        return [
            'account' => $account,
            'person' => $person,
        ];
    }

    public function findAccount(string $mobile): ?HIPUser
    {
        $mobile = HmsMobile::normalize($mobile);
        if ($mobile === '') {
            return null;
        }

        return HIPUser::query()->where('mobile_num', $mobile)->first();
    }

    private function owningAccount(Persons $person): ?HIPUser
    {
        $current = $person;

        for ($depth = 0; $depth < 5 && filled($current->parent_id); $depth++) {
            $parent = Persons::query()->find($current->parent_id);
            if (! $parent) {
                break;
            }
            $current = $parent;
        }

        if ($current->hip_user_id) {
            $owner = HIPUser::query()->find($current->hip_user_id);
            if ($owner) {
                return $owner;
            }
        }

        if (filled($current->mobile)) {
            return $this->findAccount((string) $current->mobile);
        }

        return null;
    }
}
