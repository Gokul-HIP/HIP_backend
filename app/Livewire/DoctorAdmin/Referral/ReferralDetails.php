<?php

namespace App\Livewire\DoctorAdmin\Referral;

use App\Models\Referral;
use Carbon\Carbon;
use Livewire\Component;

class ReferralDetails extends Component
{
    public Referral $referral;
    public bool $canEdit = false;
    public bool $canUpdateStatus = false;

    public function mount(int $id): void
    {
        $doctorId = $this->getDoctorId();

        $this->referral = Referral::query()
            ->with([
                'member:id,hip_id,first_name,last_name,mobile_num,gender,dob',
                'referredByDoctor:id,name',
                'referredToDoctor:id,name',
                'hospital:id,name',
            ])
            ->where('id', $id)
            ->when(
                $doctorId,
                fn ($query) => $query->where(function ($inner) use ($doctorId) {
                    $inner->where('referred_by_doctor_id', $doctorId)
                        ->orWhere('referred_to_doctor_id', $doctorId);
                }),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->firstOrFail();

        $this->canEdit = $doctorId !== null && $this->referral->referred_by_doctor_id === $doctorId;
        $this->canUpdateStatus = $doctorId !== null;
    }

    public function getMemberFullNameProperty(): string
    {
        $member = $this->referral->member;

        if ($member && trim((string) $member->full_name) !== '') {
            return $member->full_name;
        }

        return $this->referral->member_name ?: '-';
    }

    public function getMemberHipIdProperty(): string
    {
        return $this->referral->member?->hip_id
            ?? $this->referral->insurance_member_id
            ?? '-';
    }

    public function getMemberPhoneProperty(): string
    {
        $memberPhone = $this->referral->member?->mobile_num;

        if ($memberPhone) {
            return $memberPhone;
        }

        $referralPhone = trim(($this->referral->country_code ?? '') . ' ' . ($this->referral->phone_number ?? ''));

        return $referralPhone !== '' ? $referralPhone : '-';
    }

    public function getMemberGenderProperty(): string
    {
        return $this->referral->member?->gender
            ? ucfirst((string) $this->referral->member->gender)
            : '-';
    }

    public function getMemberAgeProperty(): string
    {
        $dob = $this->referral->member?->dob;

        if (!$dob) {
            return '-';
        }

        return (string) Carbon::parse($dob)->age;
    }

    public function getProgressStepProperty(): int
    {
        return match (strtolower((string) $this->referral->status)) {
            'accepted', 'progress' => 2,
            'completed' => 3,
            default => 1,
        };
    }

    public function getProgressWidthProperty(): string
    {
        return match ($this->progressStep) {
            2 => '50%',
            3 => '100%',
            default => '0%',
        };
    }

    public function getStatusOptionsProperty(): array
    {
        return [
            'pending' => 'Pending',
            'accepted' => 'Confirmed',
            'completed' => 'Completed',
            'rejected' => 'Cancelled',
        ];
    }

    public function getStatusLabelProperty(): string
    {
        return $this->statusOptions[strtolower((string) $this->referral->status)] ?? ucfirst((string) $this->referral->status);
    }

    public function getStatusBadgeClassesProperty(): string
    {
        return match (strtolower((string) $this->referral->status)) {
            'accepted' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'completed' => 'bg-sky-50 text-sky-700 border-sky-200',
            'rejected' => 'bg-rose-50 text-rose-700 border-rose-200',
            default => 'bg-amber-50 text-amber-700 border-amber-200',
        };
    }

    public function getListRouteProperty(): string
    {
        return $this->canEdit
            ? route('doctor.referral.send')
            : route('doctor.referral.receive');
    }

    public function updateStatusInstant(string $status): void
    {
        abort_unless(array_key_exists($status, $this->statusOptions), 404);

        if (!$this->canUpdateStatus) {
            return;
        }

        if ($this->referral->status === $status) {
            return;
        }

        $this->referral->update([
            'status' => $status,
        ]);

        $this->referral->refresh();

        $this->dispatch('toast', type: 'success', message: 'Referral status updated successfully.');
    }

    public function cancelReferral(): void
    {
        $this->updateStatusInstant('rejected');
    }

    private function getDoctorId(): ?int
    {
        $doctorId = session('doctor_id');

        return $doctorId ? (int) $doctorId : null;
    }

    public function render()
    {
        return view('livewire.doctor-admin.referral.referral-details');
    }
}
