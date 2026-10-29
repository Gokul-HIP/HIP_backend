<?php

namespace App\Services\Hms;

use App\Models\HIPUser;
use App\Models\HmsExternalIdMap;
use App\Models\HmsIntegration;
use App\Models\Persons;
use App\Services\Patients\PatientAccountProvisioner;

/** Finds or creates the HIP account and person for an HMS booking. Never notifies. */
class HmsPatientProvisioner
{
    public function __construct(
        private readonly PatientAccountProvisioner $accounts,
    ) {}

    /**
     * @param  array<string, mixed>  $patient
     * @return array{account: HIPUser, person: Persons, created: bool}
     */
    public function provision(HmsIntegration $integration, array $patient, int $hospitalId, ?string $primaryName = null): array
    {
        $mobile = HmsMobile::normalize((string) ($patient['mobile'] ?? ''));
        $relationship = trim((string) ($patient['relationship'] ?? 'Self'));
        $patientRef = trim((string) ($patient['patient_ref'] ?? ''));
        $isDependent = strcasecmp($relationship, 'Self') !== 0;

        if ($isDependent) {
            $primaryMobile = HmsMobile::normalize((string) ($patient['primary_mobile'] ?? ''));
            $primary = $this->primaryAccount($primaryMobile, $hospitalId, $primaryName);
            $person = $this->dependentPerson($integration, $primary['person'], $patient, $mobile, $relationship, $hospitalId, $patientRef);

            return [
                'account' => $primary['account'],
                'person' => $person['person'],
                'created' => $person['created'],
            ];
        }

        $existing = $this->mappedPerson($integration, $patientRef);
        if ($existing) {
            $account = $this->accountForPerson($existing);
            if (! $account || HmsMobile::normalize((string) $account->mobile_num) !== $mobile) {
                throw new \App\Exceptions\HmsClientException(
                    'validation_error',
                    'patient_ref does not match this mobile.',
                    422
                );
            }

            return ['account' => $account, 'person' => $existing, 'created' => false];
        }

        $account = HIPUser::query()->where('mobile_num', $mobile)->first();
        if ($account) {
            $person = Persons::query()
                ->where('hip_user_id', $account->getKey())
                ->orderByDesc('is_primary')
                ->first();
            $created = false;
            if (! $person) {
                $person = $this->ensurePrimaryPerson($account, $patient, $mobile, $hospitalId);
                $created = true;
            }
            $this->storeMap($integration, $patientRef, (string) $person->id);

            return ['account' => $account, 'person' => $person, 'created' => $created];
        }

        $createdAccount = $this->createPrimary($patient, $mobile, $hospitalId);
        $this->storeMap($integration, $patientRef, (string) $createdAccount['person']->id);

        return [
            'account' => $createdAccount['user'],
            'person' => $createdAccount['person'],
            'created' => true,
        ];
    }

    /**
     * @param  array<string, mixed>  $patient
     * @return array{account: HIPUser, person: Persons}
     */
    private function primaryAccount(string $mobile, int $hospitalId, ?string $primaryName = null): array
    {
        $name = trim((string) $primaryName);
        if ($name === '') {
            $name = $mobile;
        }

        $account = HIPUser::query()->where('mobile_num', $mobile)->first();
        if ($account) {
            $person = Persons::query()
                ->where('hip_user_id', $account->getKey())
                ->where('is_primary', true)
                ->first();
            if (! $person) {
                $person = $this->ensurePrimaryPerson($account, [
                    'name' => $name,
                    'mobile' => $mobile,
                ], $mobile, $hospitalId);
            }

            return ['account' => $account, 'person' => $person];
        }

        $created = $this->createPrimary([
            'name' => $name,
            'mobile' => $mobile,
            'relationship' => 'Self',
        ], $mobile, $hospitalId);

        return ['account' => $created['user'], 'person' => $created['person']];
    }

    /**
     * @param  array<string, mixed>  $patient
     * @return array{person: Persons, created: bool}
     */
    private function dependentPerson(
        HmsIntegration $integration,
        Persons $primary,
        array $patient,
        string $mobile,
        string $relationship,
        int $hospitalId,
        string $patientRef,
    ): array {
        $mapped = $this->mappedPerson($integration, $patientRef);
        if ($mapped) {
            if ((string) $mapped->parent_id !== (string) $primary->id) {
                throw new \App\Exceptions\HmsClientException(
                    'validation_error',
                    'patient_ref does not match this mobile.',
                    422
                );
            }

            return ['person' => $mapped, 'created' => false];
        }

        [$first, $last] = $this->splitName((string) ($patient['name'] ?? ''));
        $existing = $this->matchingDependent($primary, $first, $last, $patient['dob'] ?? null);
        if ($existing) {
            $this->storeMap($integration, $patientRef, (string) $existing->id);

            return ['person' => $existing, 'created' => false];
        }

        $person = $this->accounts->createDependent((string) $primary->id, [
            'mrn' => $this->accounts->nextMrn(),
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'dob' => $patient['dob'] ?? null,
            'gender' => $patient['gender'] ?? null,
            'email' => $patient['email'] ?? null,
            'relationship' => $relationship,
            'hospital_id' => $hospitalId,
            'is_active' => true,
            'deactivated_at' => null,
        ]);
        $this->storeMap($integration, $patientRef, (string) $person->id);

        return ['person' => $person, 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $patient
     * @return array{user: HIPUser, person: Persons}
     */
    private function createPrimary(array $patient, string $mobile, int $hospitalId): array
    {
        [$first, $last] = $this->splitName((string) ($patient['name'] ?? $mobile));
        return $this->accounts->createPrimary([
            'mrn' => $this->accounts->nextMrn(),
            'first_name' => $first,
            'last_name' => $last,
            'mobile' => $mobile,
            'dob' => $patient['dob'] ?? null,
            'gender' => $patient['gender'] ?? null,
            'email' => $patient['email'] ?? null,
            'relationship' => 'Self',
            'hospital_id' => $hospitalId,
            'is_active' => true,
            'deactivated_at' => null,
        ], [
            'hip_id' => $this->accounts->nextHipId(),
            'first_name' => $first,
            'last_name' => $last,
            'email' => $patient['email'] ?? null,
            'mobile_num' => $mobile,
            'gender' => $patient['gender'] ?? null,
            'dob' => $patient['dob'] ?? null,
            'password' => null,
            'is_active' => true,
            'deactivated_at' => null,
        ]);
    }

    private function matchingDependent(Persons $primary, string $first, string $last, mixed $dob): ?Persons
    {
        if ($first === '' || ! filled($dob)) {
            return null;
        }

        return Persons::query()
            ->where('parent_id', $primary->id)
            ->where('is_primary', false)
            ->whereRaw('LOWER(first_name) = ?', [strtolower($first)])
            ->whereRaw('LOWER(COALESCE(last_name, "")) = ?', [strtolower($last)])
            ->whereDate('dob', (string) $dob)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $patient
     */
    private function ensurePrimaryPerson(HIPUser $account, array $patient, string $mobile, int $hospitalId): Persons
    {
        [$first, $last] = $this->splitName((string) ($patient['name'] ?? $account->first_name ?? $mobile));

        return $this->accounts->attachPrimaryPerson($account, [
            'mrn' => $this->accounts->nextMrn(),
            'first_name' => $first !== '' ? $first : (string) ($account->first_name ?? $mobile),
            'last_name' => $last,
            'mobile' => $mobile,
            'dob' => $patient['dob'] ?? $account->dob,
            'gender' => $patient['gender'] ?? $account->gender,
            'email' => $patient['email'] ?? $account->email,
            'relationship' => 'Self',
            'hospital_id' => $hospitalId,
            'is_active' => true,
            'deactivated_at' => null,
        ]);
    }

    private function mappedPerson(HmsIntegration $integration, string $patientRef): ?Persons
    {
        if ($patientRef === '') {
            return null;
        }

        $mapped = HmsExternalIdMap::query()
            ->where('integration_id', $integration->getKey())
            ->where('entity_type', HmsExternalIdMap::TYPE_PATIENT)
            ->where('external_id', $patientRef)
            ->first();

        return $mapped ? Persons::query()->find($mapped->local_id) : null;
    }

    private function storeMap(HmsIntegration $integration, string $patientRef, string $personId): void
    {
        if ($patientRef === '') {
            return;
        }

        HmsExternalIdMap::query()->updateOrCreate(
            [
                'integration_id' => $integration->getKey(),
                'entity_type' => HmsExternalIdMap::TYPE_PATIENT,
                'external_id' => $patientRef,
            ],
            ['local_id' => $personId]
        );
    }

    private function accountForPerson(Persons $person): ?HIPUser
    {
        $current = $person;
        for ($depth = 0; $depth < 5 && filled($current->parent_id) && (string) $current->parent_id !== (string) $current->id; $depth++) {
            $parent = Persons::query()->find($current->parent_id);
            if (! $parent) {
                break;
            }
            $current = $parent;
        }

        if ($current->hip_user_id) {
            return HIPUser::query()->find($current->hip_user_id);
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $name, 2) ?: [$name];

        return [$parts[0], $parts[1] ?? ''];
    }
}
