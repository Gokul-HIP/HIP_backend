<?php

namespace App\Policies;

use App\Enums\PharmacyOrderStatus;
use App\Models\HIPUser;
use App\Models\Persons;
use App\Models\PharmacyOrder;
use App\Services\PharmacistScopeService;

class PharmacyOrderPolicy
{
    public function patientCanView(HIPUser $user, PharmacyOrder $order): bool
    {
        return $this->belongsToFamily($user, $order);
    }

    public function patientCanCancel(HIPUser $user, PharmacyOrder $order): bool
    {
        if (! $this->belongsToFamily($user, $order)) {
            return false;
        }

        $status = $order->status instanceof PharmacyOrderStatus
            ? $order->status
            : PharmacyOrderStatus::tryFrom((string) $order->status);

        return in_array($status, [
            PharmacyOrderStatus::Requested,
            PharmacyOrderStatus::InReview,
            PharmacyOrderStatus::PendingPayment,
        ], true);
    }

    public function pharmacistCanManage(HIPUser $user, PharmacyOrder $order): bool
    {
        if (! $user->hasRole('pharmacist')) {
            return false;
        }

        return in_array(
            (int) $order->pharmacy_id,
            app(PharmacistScopeService::class)->pharmacyIds($user),
            true
        );
    }

    /**
     * Same family rule as HomePageController::resolveDocumentPatientForUser.
     * Used before an order exists, when the client sends patient_id.
     */
    public function canUsePerson(HIPUser $user, string $personId): bool
    {
        $primary = Persons::query()
            ->where('hip_user_id', $user->id)
            ->where('is_primary', true)
            ->first()
            ?? Persons::query()->where('hip_user_id', $user->id)->first();

        if (! $primary) {
            return false;
        }

        $person = Persons::query()->find($personId);

        if (! $person) {
            return false;
        }

        $familyRootId = (string) ($primary->parent_id ?? $primary->id);

        return (string) $person->id === (string) $primary->id
            || (string) ($person->parent_id ?? '') === $familyRootId
            || (string) ($person->hip_user_id ?? '') === (string) $user->id;
    }

    /**
     * Same family rule as HomePageController::resolveDocumentPatientForUser.
     */
    private function belongsToFamily(HIPUser $user, PharmacyOrder $order): bool
    {
        if ((string) $order->hip_user_id === (string) $user->id) {
            return true;
        }

        if (! $order->person_id) {
            return false;
        }

        return $this->canUsePerson($user, (string) $order->person_id);
    }
}
