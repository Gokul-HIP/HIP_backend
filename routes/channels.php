<?php

use App\Models\HIPUser;
use App\Models\PharmacyOrder;
use App\Policies\PharmacyOrderPolicy;
use App\Services\PharmacistScopeService;
use Illuminate\Support\Facades\Broadcast;

// Doctor admin panel (session auth via Filament guard).
Broadcast::channel('doctor.{doctorId}', function ($user, string $doctorId) {
    if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('doctor')) {
        return false;
    }

    $sessionDoctorId = session('doctor_id');

    return filled($sessionDoctorId) && (string) $sessionDoctorId === (string) $doctorId;
});

// Mobile app users (Sanctum bearer token). Only the owner may subscribe.
Broadcast::channel('user.{userId}', function ($user, string $userId) {
    if (! $user instanceof HIPUser) {
        return false;
    }

    return (string) $user->id === (string) $userId;
});

// Pharmacist panel. Same filament session guard as /broadcasting/auth.
// pharmacy-order uses PharmacyOrderPolicy::pharmacistCanManage because the policy has no view() method.
Broadcast::channel('pharmacy.{pharmacyId}', function ($user, string $pharmacyId) {
    if (! $user instanceof HIPUser || ! method_exists($user, 'hasRole') || ! $user->hasRole('pharmacist')) {
        return false;
    }

    return in_array((int) $pharmacyId, app(PharmacistScopeService::class)->pharmacyIds($user), true);
});

Broadcast::channel('pharmacy-order.{id}', function ($user, string $id) {
    if (! $user instanceof HIPUser || ! method_exists($user, 'hasRole') || ! $user->hasRole('pharmacist')) {
        return false;
    }

    $order = PharmacyOrder::query()->find($id);
    if (! $order) {
        return false;
    }

    $policy = app(PharmacyOrderPolicy::class);

    if (method_exists($policy, 'view')) {
        return (bool) $policy->view($user, $order);
    }

    return $policy->pharmacistCanManage($user, $order);
});
