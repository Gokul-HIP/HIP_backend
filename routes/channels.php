<?php

use App\Models\HIPUser;
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
