<?php

namespace App\Livewire\DoctorAdmin\Concerns;

use App\Support\DoctorPatientViewData;

trait ManagesPatientProfilePanel
{
    public bool $showPatientProfilePanel = false;

    public ?array $profilePatient = null;

    public function openPatientProfile(int $bookingId): void
    {
        $booking = $this->findDoctorBooking($bookingId);

        if (! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Patient profile is unavailable.');

            return;
        }

        $this->profilePatient = DoctorPatientViewData::buildPatientProfile($booking, $this->doctor());
        $this->showPatientProfilePanel = true;
    }

    public function closePatientProfile(): void
    {
        $this->showPatientProfilePanel = false;
        $this->profilePatient = null;
    }

    public function openFullHistoryFromProfile(): void
    {
        if (! $this->profilePatient || empty($this->profilePatient['booking_id'])) {
            return;
        }

        $bookingId = (int) $this->profilePatient['booking_id'];
        $this->closePatientProfile();
        $this->openAppointmentHistory($bookingId);
    }
}
