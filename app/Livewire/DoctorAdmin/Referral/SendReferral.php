<?php

namespace App\Livewire\DoctorAdmin\Referral;

use App\Models\Referral;
use Flux\Flux;
use Livewire\Component;
use Livewire\WithPagination;

class SendReferral extends Component
{
    use WithPagination;

    public int $perPage = 10;
    public ?int $deleteReferralId = null;
    public ?string $deleteReferralName = null;

    public function getStatusCountsProperty(): array
    {
        $doctorId = $this->getDoctorId();

        if (!$doctorId) {
            return [
                'pending' => 0,
                'accepted' => 0,
                'completed' => 0,
                'rejected' => 0,
            ];
        }

        $counts = Referral::query()
            ->where('referred_by_doctor_id', $doctorId)
            ->selectRaw("
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            ")
            ->first();

        return [
            'pending' => (int) ($counts?->pending ?? 0),
            'accepted' => (int) ($counts?->accepted ?? 0),
            'completed' => (int) ($counts?->completed ?? 0),
            'rejected' => (int) ($counts?->rejected ?? 0),
        ];
    }

    private function getDoctorId(): ?int
    {
        $doctorId = session('doctor_id');

        return $doctorId ? (int) $doctorId : null;
    }

    public function openDeleteModal(int $referralId): void
    {
        $doctorId = $this->getDoctorId();

        if (!$doctorId) {
            return;
        }

        $referral = Referral::query()
            ->where('id', $referralId)
            ->where('referred_by_doctor_id', $doctorId)
            ->first();

        if (!$referral) {
            return;
        }

        $this->deleteReferralId = $referral->id;
        $this->deleteReferralName = $referral->member_name ?: 'this referral';

        Flux::modal('delete-referral')->show();
    }

    public function closeDeleteModal(): void
    {
        $this->deleteReferralId = null;
        $this->deleteReferralName = null;

        Flux::modal('delete-referral')->close();
    }

    public function deleteReferral(): void
    {
        $doctorId = $this->getDoctorId();

        if (!$doctorId || !$this->deleteReferralId) {
            $this->closeDeleteModal();
            return;
        }

        Referral::query()
            ->where('id', $this->deleteReferralId)
            ->where('referred_by_doctor_id', $doctorId)
            ->delete();

        $this->dispatch('toast', type: 'success', message: 'Referral deleted successfully.');

        $this->closeDeleteModal();

        $this->resetPage();
    }

    public function render()
    {
        $doctorId = $this->getDoctorId();

        $referrals = Referral::query()
            ->with([
                'referredToDoctor:id,name',
                'hospital:id,name',
            ])
            ->when(
                $doctorId,
                fn ($query) => $query->where('referred_by_doctor_id', $doctorId),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.doctor-admin.referral.send-referral', [
            'referrals' => $referrals,
            'statusCounts' => $this->statusCounts,
        ]);
    }
}
