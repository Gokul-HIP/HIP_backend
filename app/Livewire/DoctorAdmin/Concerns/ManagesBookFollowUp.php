<?php

namespace App\Livewire\DoctorAdmin\Concerns;

use App\Models\Doctor;
use App\Models\DoctorBooking;
use App\Models\Hospital;
use App\Services\DoctorFollowUpService;
use App\Support\DoctorPatientViewData;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait ManagesBookFollowUp
{
    public bool $showBookFollowUpModal = false;

    public ?int $followUpSourceBookingId = null;

    public ?array $followUpPatient = null;

    public string $followUpType = 'in-clinic';

    public string $followUpBranch = '';

    public string $followUpDate = '';

    public string $followUpTime = '09:00';

    public string $followUpReason = 'review-reports';

    public string $followUpNotes = '';

    public bool $sendNotificationReminder = true;

    public function bookFollowUp(?int $bookingId = null): void
    {
        $this->openBookFollowUp($bookingId);
    }

    public function bookFollowUpFromHistory(): void
    {
        $bookingId = $this->historyBookingId ?? null;
        $this->closeAppointmentHistory();
        $this->openBookFollowUp($bookingId);
    }

    public function openBookFollowUp(?int $bookingId = null): void
    {
        $bookingId = $bookingId
            ?? ($this->profilePatient['booking_id'] ?? null)
            ?? $this->historyBookingId
            ?? $this->followUpSourceBookingId;

        if (! $bookingId) {
            $this->dispatch('toast', type: 'error', message: 'Unable to open follow-up booking.');

            return;
        }

        $booking = $this->findDoctorBooking((int) $bookingId);

        if (! $booking) {
            $this->dispatch('toast', type: 'error', message: 'Patient booking not found.');

            return;
        }

        $doctor = $this->doctor();
        $branches = $this->followUpBranches($doctor);

        if ($branches->isEmpty()) {
            $this->dispatch('toast', type: 'error', message: 'No hospital branches are available for this doctor.');

            return;
        }

        $profile = DoctorPatientViewData::buildPatientProfile($booking, $doctor);
        $lastVisit = DoctorPatientViewData::patientBookings($booking, $doctor)
            ->filter(fn (DoctorBooking $item) => $item->booking_date && $item->id !== $booking->id)
            ->sortByDesc(fn (DoctorBooking $item) => $item->booking_date?->timestamp ?? 0)
            ->first();

        $defaultBranch = (string) ($booking->branch_id ?: $booking->hospital_id ?: $branches->first()->id);

        if (! $branches->contains('id', (int) $defaultBranch)) {
            $defaultBranch = (string) $branches->first()->id;
        }

        $department = $booking->department?->name
            ?: (($doctor?->speciality_names ?? '-') !== '-' ? $doctor?->speciality_names : 'General Medicine');

        $this->followUpSourceBookingId = $booking->id;
        $this->followUpPatient = [
            'name' => $profile['name'],
            'uhid' => $profile['uhid'],
            'age' => $profile['age'],
            'gender' => $profile['gender'],
            'mobile' => $profile['mobile'],
            'avatar_url' => $profile['avatar_url'],
            'primary_doctor' => $profile['primary_doctor'],
            'department' => $department,
            'prev_visit' => $lastVisit?->booking_date?->format('d M Y')
                ?? $booking->booking_date?->format('d M Y')
                ?? '—',
        ];
        $this->followUpType = $booking->isOnlineConsultation() ? 'online' : 'in-clinic';
        $this->followUpBranch = $defaultBranch;
        $this->followUpDate = now()->addDays(7)->toDateString();
        $this->followUpTime = '09:00';
        $this->followUpReason = 'review-reports';
        $this->followUpNotes = '';
        $this->sendNotificationReminder = true;
        $this->showBookFollowUpModal = true;
    }

    public function closeBookFollowUp(): void
    {
        $this->showBookFollowUpModal = false;
        $this->followUpSourceBookingId = null;
        $this->followUpPatient = null;
    }

    public function bookFollowUpAppointment(DoctorFollowUpService $followUpService): void
    {
        $this->validate([
            'followUpBranch' => 'required',
            'followUpDate' => 'required|date|after_or_equal:today',
            'followUpTime' => 'required',
            'followUpReason' => 'required|string',
            'followUpType' => 'required|in:in-clinic,online',
            'followUpNotes' => 'nullable|string|max:2000',
        ], [
            'followUpDate.after_or_equal' => 'Follow-up date must be today or a future date.',
        ]);

        $doctor = $this->doctor();
        $sourceBooking = $this->findDoctorBooking($this->followUpSourceBookingId);

        if (! $doctor || ! $sourceBooking) {
            $this->dispatch('toast', type: 'error', message: 'Follow-up booking could not be completed.');

            return;
        }

        $branchExists = $this->followUpBranches($doctor)->contains('id', (int) $this->followUpBranch);

        if (! $branchExists) {
            $this->addError('followUpBranch', 'Please select a valid hospital branch.');

            return;
        }

        try {
            $followUpService->createFollowUpBooking($doctor, $sourceBooking, [
                'follow_up_type' => $this->followUpType,
                'branch_id' => (int) $this->followUpBranch,
                'booking_date' => $this->followUpDate,
                'booking_time' => $this->followUpTime,
                'follow_up_reason' => $this->followUpReason,
                'clinical_notes' => $this->followUpNotes,
                'send_notification_reminder' => $this->sendNotificationReminder,
            ]);
        } catch (\InvalidArgumentException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());

            return;
        } catch (\Throwable) {
            $this->dispatch('toast', type: 'error', message: 'Failed to book follow-up appointment.');

            return;
        }

        $this->closeBookFollowUp();
        $this->dispatch('toast', type: 'success', message: 'Follow-up appointment booked successfully.');
    }

    public function getFollowUpBranchLabelProperty(): string
    {
        $doctor = $this->doctor();

        if (! $doctor || $this->followUpBranch === '') {
            return '—';
        }

        return $this->followUpBranches($doctor)
            ->firstWhere('id', (int) $this->followUpBranch)
            ?->name ?? '—';
    }

    public function getFollowUpTypeLabelProperty(): string
    {
        return $this->followUpType === 'online' ? 'Online' : 'In-Clinic';
    }

    public function getFollowUpDateLabelProperty(): string
    {
        if ($this->followUpDate === '') {
            return '—';
        }

        try {
            return Carbon::parse($this->followUpDate)->format('d M Y');
        } catch (\Throwable) {
            return $this->followUpDate;
        }
    }

    public function getFollowUpTimeLabelProperty(): string
    {
        if ($this->followUpTime === '') {
            return '—';
        }

        try {
            return Carbon::createFromFormat('H:i', $this->followUpTime)->format('h:i A');
        } catch (\Throwable) {
            return $this->followUpTime;
        }
    }

    protected function followUpBranches(?Doctor $doctor): Collection
    {
        if (! $doctor) {
            return collect();
        }

        $query = Hospital::query()->orderBy('name');

        if (filled($doctor->organization_id)) {
            $query->where('organization_id', $doctor->organization_id);
        } else {
            $hospitalIds = collect($doctor->hospital_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values()
                ->all();

            if ($hospitalIds === []) {
                return collect();
            }

            $query->whereIn('id', $hospitalIds);
        }

        return $query->get(['id', 'name']);
    }

    protected function followUpFormData(): array
    {
        $doctor = $this->doctor();

        return [
            'followUpBranches' => $this->followUpBranches($doctor),
            'followUpReasons' => DoctorFollowUpService::reasonOptions(),
            'followUpBranchLabel' => $this->followUpBranchLabel,
            'followUpTypeLabel' => $this->followUpTypeLabel,
            'followUpDateLabel' => $this->followUpDateLabel,
            'followUpTimeLabel' => $this->followUpTimeLabel,
        ];
    }
}
