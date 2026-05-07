<?php

namespace App\Livewire\DoctorAdmin\Referral;

use App\Models\Doctor;
use App\Models\DoctorCredential;
use App\Models\Referral;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
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

    private function getDoctorId(): ?string
    {
        $doctorId = session('doctor_id');
        if (filled($doctorId)) {
            return (string) $doctorId;
        }

        $user = Auth::guard('filament')->user() ?? Auth::user();
        if (!$user) {
            return null;
        }

        $authDoctorId = $user->doctor_id ?? null;
        if (filled($authDoctorId)) {
            session()->put('doctor_id', (string) $authDoctorId);
            return (string) $authDoctorId;
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if ($email !== '') {
            $credentialDoctorId = DoctorCredential::query()
                ->whereRaw('LOWER(email) = ?', [$email], 'and')
                ->value('doctor_id');
            if (filled($credentialDoctorId)) {
                session()->put('doctor_id', (string) $credentialDoctorId);
                return (string) $credentialDoctorId;
            }

            $doctorIdByEmail = Doctor::query()
                ->whereRaw('LOWER(email) = ?', [$email], 'and')
                ->value('id');
            if (filled($doctorIdByEmail)) {
                session()->put('doctor_id', (string) $doctorIdByEmail);
                return (string) $doctorIdByEmail;
            }
        }

        $organizationId = (int) ($user->organization_id ?? 0);
        $name = strtolower(trim((string) ($user->name ?? '')));
        if ($organizationId > 0 && $name !== '') {
            $doctorIdByName = Doctor::query()
                ->where('organization_id', $organizationId)
                ->whereRaw('LOWER(name) = ?', [$name], 'and')
                ->value('id');
            if (filled($doctorIdByName)) {
                session()->put('doctor_id', (string) $doctorIdByName);
                return (string) $doctorIdByName;
            }
        }

        $mobile = preg_replace('/\D+/', '', (string) ($user->mobile_num ?? $user->mobile_number ?? ''));
        if (!empty($mobile)) {
            $doctorIdByMobile = Doctor::query()
                ->when($organizationId > 0, fn ($q) => $q->where('organization_id', $organizationId))
                ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE(mobile_number, ''), '+', ''), '-', ''), ' ', ''), '(', ''), ')', '') = ?", [$mobile], 'and')
                ->value('id');
            if (filled($doctorIdByMobile)) {
                session()->put('doctor_id', (string) $doctorIdByMobile);
                return (string) $doctorIdByMobile;
            }
        }

        if ($organizationId > 0) {
            $singleDoctorInOrg = Doctor::query()
                ->where('organization_id', $organizationId)
                ->orderBy('name')
                ->value('id');
            if (filled($singleDoctorInOrg)) {
                session()->put('doctor_id', (string) $singleDoctorInOrg);
                return (string) $singleDoctorInOrg;
            }
        }

        return null;
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
                'member:id,hip_id',
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
