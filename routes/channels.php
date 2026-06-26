<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('doctor.{doctorId}', function ($user, string $doctorId) {
    if (! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole('doctor')) {
        return false;
    }

    $sessionDoctorId = session('doctor_id');

    return filled($sessionDoctorId) && (string) $sessionDoctorId === (string) $doctorId;
});
