<?php

namespace App\Modules\Automation\Support;

use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Persons;

final class PatientAutomationIdentity
{
    /**
     * HIP account-holder registration creates Persons with is_primary=true
     * (AuthService, BookingApiService, DesktopController). Dependents are created
     * with is_primary=false (e.g. technician family-member add).
     */
    public static function isPrimaryAccountPerson(Persons $person): bool
    {
        return filter_var($person->is_primary, FILTER_VALIDATE_BOOLEAN);
    }

    public static function ensureUserLoaded(Persons $person): void
    {
        if ($person->hip_user_id && ! $person->relationLoaded('hipUser') && $person->exists) {
            $person->load('hipUser');
        }
    }

    public static function hipUser(Persons $person): ?HIPUser
    {
        self::ensureUserLoaded($person);

        if (! $person->relationLoaded('hipUser')) {
            return null;
        }

        $related = $person->getRelation('hipUser');

        return $related instanceof HIPUser ? $related : null;
    }

    public static function organizationId(Persons $person): ?int
    {
        $value = self::hipUser($person)?->organization_id;

        return is_numeric($value) ? (int) $value : null;
    }

    public static function hospitalId(Persons $person): mixed
    {
        $user = self::hipUser($person);

        if ($user instanceof HIPUser && filled($user->hospital_id)) {
            return $user->hospital_id;
        }

        if (! $person->exists) {
            return null;
        }

        return DoctorBooking::query()
            ->where('patient_id', $person->id)
            ->whereNotNull('hospital_id')
            ->orderByDesc('booking_date')
            ->orderByDesc('id')
            ->value('hospital_id');
    }
}
