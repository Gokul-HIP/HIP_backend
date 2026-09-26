<?php

namespace App\Livewire\Concerns;

use App\Models\DoctorBooking;
use App\Services\DoctorBookingStatusService;
use Carbon\Carbon;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

trait ReschedulesDoctorBooking
{
    public $id;
    public string $aptId = '';
    public string $patientName = '';
    public string $doctorName = '';
    public string $currentDateLabel = '';
    public string $currentTimeLabel = '';
    public string $newDate = '';
    public string $newTime = '';
    public string $refreshEvent = 'refreshDoctorBookings';

    abstract protected function findRescheduleBooking(int $id): ?DoctorBooking;

    #[On('openRescheduleModal')]
    public function openRescheduleModal($id): void
    {
        $booking = $this->findRescheduleBooking((int) $id);

        if (! $booking) {
            return;
        }

        $booking->loadMissing('doctor');

        $slots = is_array($booking->required_time_slots)
            ? array_values(array_filter($booking->required_time_slots, fn ($slot) => filled($slot)))
            : [];
        $currentTime = isset($slots[0]) ? (string) $slots[0] : '';

        $this->id = $booking->id;
        $this->aptId = 'APT'.str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
        $this->patientName = (string) ($booking->name ?: '-');
        $this->doctorName = $booking->doctor?->name ? 'Dr '.$booking->doctor->name : '-';
        $this->currentDateLabel = $booking->booking_date?->format('M d, Y') ?: '-';
        $this->currentTimeLabel = $currentTime !== '' ? $currentTime : '-';
        $this->newDate = $booking->booking_date?->toDateString() ?: '';
        $this->newTime = $this->timeInputValue($currentTime);
        $this->resetErrorBag();

        Flux::modal('reschedule-appointment')->show();
    }

    public function rescheduleAppointment(DoctorBookingStatusService $statusService): void
    {
        $this->validate([
            'newDate' => 'required|date|after_or_equal:today',
            'newTime' => 'required|date_format:H:i',
        ]);

        $booking = $this->findRescheduleBooking((int) $this->id);

        if (! $booking) {
            $this->closeRescheduleModal();

            return;
        }

        try {
            $updated = $statusService->reschedule($booking, $this->newDate, $this->newTime);
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->getMessageBag());

            return;
        }

        $this->dispatch($this->refreshEvent);
        $this->closeRescheduleModal();
        $this->dispatch('toast', type: 'success', message: 'Appointment rescheduled for '.$updated->name.' successfully!');
    }

    public function closeRescheduleModal(): void
    {
        $this->reset([
            'id',
            'aptId',
            'patientName',
            'doctorName',
            'currentDateLabel',
            'currentTimeLabel',
            'newDate',
            'newTime',
        ]);
        $this->resetErrorBag();
        Flux::modal('reschedule-appointment')->close();
    }

    protected function timeInputValue(string $slot): string
    {
        if (trim($slot) === '') {
            return '';
        }

        try {
            return Carbon::parse($slot)->format('H:i');
        } catch (\Throwable) {
            return '';
        }
    }
}
