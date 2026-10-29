<?php

namespace App\Services\Patients;

use App\Models\Coins;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Services\RewardTierService;
use Illuminate\Support\Facades\DB;

/**
 * Creates the same HIP account and person rows as the admin patient form.
 * Opens a zero-balance coin wallet and the base reward-tier progress row.
 * Does not send notifications.
 */
class PatientAccountProvisioner
{
    public function __construct(private readonly RewardTierService $rewards) {}
    /**
     * @param  array<string, mixed>  $personAttributes
     * @param  array<string, mixed>  $userAttributes
     * @return array{user: HIPUser, person: Persons}
     */
    public function createPrimary(array $personAttributes, array $userAttributes): array
    {
        if (! filled($userAttributes['hip_id'] ?? null)) {
            $userAttributes['hip_id'] = $this->nextHipId();
        }

        $user = HIPUser::query()->create($userAttributes);

        $person = $this->storePrimaryPerson($personAttributes, (string) $user->id);
        Coins::query()->firstOrCreate(
            ['person_id' => $person->id],
            ['coins' => 0]
        );
        $this->rewards->getUserProgress((string) $user->id);

        return [
            'user' => $user->fresh(),
            'person' => $person,
        ];
    }

    /**
     * @param  array<string, mixed>  $personAttributes
     */
    public function attachPrimaryPerson(HIPUser $user, array $personAttributes): Persons
    {
        return $this->storePrimaryPerson($personAttributes, (string) $user->id);
    }

    /**
     * @param  array<string, mixed>  $personAttributes
     */
    private function storePrimaryPerson(array $personAttributes, string $hipUserId): Persons
    {
        $personAttributes['hip_user_id'] = $hipUserId;
        $personAttributes['is_primary'] = true;
        $personAttributes['parent_id'] = null;
        $person = Persons::query()->create($personAttributes);
        $person->update(['parent_id' => $person->id]);

        return $person->fresh();
    }

    /**
     * @param  array<string, mixed>  $personAttributes
     */
    public function createDependent(string $primaryPersonId, array $personAttributes): Persons
    {
        $personAttributes['hip_user_id'] = null;
        $personAttributes['is_primary'] = false;
        $personAttributes['parent_id'] = $primaryPersonId;

        return Persons::query()->create($personAttributes);
    }

    public function nextMrn(): string
    {
        return DB::transaction(function () {
            $max = 0;
            $mrns = DB::table('persons')->where('mrn', 'like', 'MRN%')->pluck('mrn');
            foreach ($mrns as $mrn) {
                if (preg_match('/^MRN(\d+)$/', (string) $mrn, $matches) === 1) {
                    $max = max($max, (int) $matches[1]);
                }
            }

            return 'MRN'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
        });
    }

    public function nextHipId(): string
    {
        $max = 0;
        $ids = DB::table('healthinpocket_users')->where('hip_id', 'like', 'HIP%')->pluck('hip_id');
        foreach ($ids as $hipId) {
            if (preg_match('/^HIP(\d+)$/', (string) $hipId, $matches) === 1) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return 'HIP'.str_pad((string) ($max + 1), 5, '0', STR_PAD_LEFT);
    }
}
