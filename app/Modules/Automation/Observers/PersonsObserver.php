<?php

namespace App\Modules\Automation\Observers;

use App\Models\Persons;
use App\Modules\Automation\Events\PatientRegistered;
use App\Modules\Automation\Support\AfterCommit;
use App\Modules\Automation\Support\EventDispatchGuard;
use App\Modules\Automation\Support\PatientAutomationIdentity;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches PatientRegistered for HIP account-holder registration only.
 *
 * AuthService / BookingApiService / DesktopController create Persons with
 * is_primary=true (or attach hip_user_id to such a row). Family dependents are
 * created with is_primary=false and must not start first-appointment nurturing.
 */
class PersonsObserver
{
    public function created(Persons $person): void
    {
        $this->dispatchIfPrimaryRegistration($person, 'created');
    }

    public function updated(Persons $person): void
    {
        if (! $person->wasChanged('hip_user_id')) {
            return;
        }

        $previous = $person->getOriginal('hip_user_id');
        if (filled($previous) || blank($person->hip_user_id)) {
            return;
        }

        $this->dispatchIfPrimaryRegistration($person, 'hip_user_attached');
    }

    protected function dispatchIfPrimaryRegistration(Persons $person, string $reason): void
    {
        if (! PatientAutomationIdentity::isPrimaryAccountPerson($person)) {
            Log::info('[patient-registered] Skipping non-primary person', [
                'patient_id' => $person->id,
                'reason' => $reason,
                'is_primary' => $person->is_primary,
            ]);

            return;
        }

        if (! EventDispatchGuard::claim('patientRegistered:'.$person->id, now()->addDays(2))) {
            Log::info('[patient-registered] Skipping duplicate dispatch', [
                'patient_id' => $person->id,
                'reason' => $reason,
            ]);

            return;
        }

        $organizationId = PatientAutomationIdentity::organizationId($person);
        $hospitalId = PatientAutomationIdentity::hospitalId($person);

        Log::info('[patient-registered] Dispatching', [
            'patient_id' => $person->id,
            'organization_id' => $organizationId,
            'hospital_id' => $hospitalId,
            'reason' => $reason,
        ]);

        AfterCommit::run(fn () => PatientRegistered::dispatch($person, $organizationId, $hospitalId));
    }
}
