<?php

namespace App\Livewire\DoctorAdmin\Referral;

use App\Models\Doctor;
use App\Models\DoctorCredential;
use App\Models\Referral;
use App\Services\Referral\ReferralMemberResolver;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AddReferral extends Component
{
    public int $step = 1;

    public $doctor_id;
    public $member_name = '';
    public $phone_code = '+91';
    public $phone_number = '';
    public $referral_date = '';
    public $member_id = '';
    public $medical_notes = '';

    private ?ReferralMemberResolver $memberResolver = null;

    public function mount(): void
    {
        $this->referral_date = now()->format('Y-m-d');
    }

    public function updatedPhoneNumber(): void
    {
        if (blank(trim((string) $this->member_id))) {
            $resolved = $this->resolveMember();
            $this->member_id = $resolved['stored_member_id'] ?? '';
        }
    }

    public function updatedMemberId(): void
    {
        $resolved = $this->resolveMember();

        if (!empty($resolved['member']) && !blank($this->member_id)) {
            $this->member_id = $resolved['stored_member_id'] ?? $this->member_id;
        }
    }

    public function getDoctorsProperty()
    {
        $currentDoctorId = $this->getCurrentDoctorId();

        $organizationId = (int) ($this->getAuthUser()?->organization_id ?? 0);
        if ($organizationId <= 0) {
            return collect();
        }

        return Doctor::query()
            ->where('organization_id', $organizationId)
            ->when($currentDoctorId, fn ($q) => $q->where('id', '!=', $currentDoctorId))
            ->orderBy('name')
            ->get();
    }

    public function getDoctorOptions(): array
    {
        return $this->doctors
            ->map(fn ($doctor) => [
                'id' => (string) $doctor->id,
                'name' => $doctor->name,
            ])
            ->values()
            ->toArray();
    }

    public function nextStep(): void
    {
        $this->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $this->step = 2;
    }

    public function prevStep(): void
    {
        $this->step = 1;
    }

    public function cancel()
    {
        return redirect()->route('doctor.referral.send');
    }

    public function saveDraft(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        if ($this->createReferral('draft')) {
            $this->redirectRoute('doctor.referral.send');
        }
    }

    public function submit(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'member_name' => 'required|string|max:255',
            'phone_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
            'referral_date' => 'required|date',
            'member_id' => 'nullable|string|max:100',
            'medical_notes' => 'required|string|min:10',
        ]);

        if ($this->createReferral('submitted')) {
            $this->redirectRoute('doctor.referral.send');
        }
    }

    private function createReferral(string $saveState): bool
    {
        $currentDoctorId = $this->getCurrentDoctorId();
        if (blank($currentDoctorId) || !Doctor::query()->whereKey($currentDoctorId)->exists()) {
            $this->addError('doctor_id', 'Logged-in doctor mapping is invalid. Please logout and login again.');
            $this->dispatch('toast', type: 'error', message: 'Unable to map logged-in doctor. Please logout/login and try again.');
            return false;
        }

        $resolved = $this->resolveMember();

        try {
            Referral::create([
                'referred_by_doctor_id' => $currentDoctorId,
                'referred_to_doctor_id' => $this->doctor_id,
                'member_user_id' => $resolved['member_user_id'],
                'member_name' => $this->member_name ?: null,
                'country_code' => $this->phone_code,
                'phone_number' => $this->phone_number ?: null,
                'referral_date' => $this->referral_date ?: null,
                'insurance_member_id' => $resolved['stored_member_id'],
                'medical_notes' => $this->medical_notes ?: null,
                'save' => $saveState,
                'status' => 'pending',
            ]);
        } catch (\Throwable $e) {
            Log::error('Doctor referral create failed', [
                'doctor_id' => $currentDoctorId,
                'referred_to_doctor_id' => $this->doctor_id,
                'save_state' => $saveState,
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast', type: 'error', message: 'Referral could not be saved. Please try again.');
            return false;
        }

        $this->dispatch(
            'toast',
            type: 'success',
            message: $saveState === 'draft'
                ? 'Referral draft saved successfully.'
                : 'Referral created successfully.'
        );

        return true;
    }

    private function resolveMember(): array
    {
        return $this->memberResolver()
            ->resolve($this->member_id, $this->phone_number);
    }

    private function memberResolver(): ReferralMemberResolver
    {
        return $this->memberResolver ??= app(ReferralMemberResolver::class);
    }

    private function getCurrentDoctorId(): ?string
    {
        $doctorId = session('doctor_id');
        if (filled($doctorId)) {
            return (string) $doctorId;
        }

        $user = $this->getAuthUser();
        if (!$user) {
            return null;
        }

        $authDoctorId = $user->doctor_id ?? null;
        if (filled($authDoctorId)) {
            return (string) $authDoctorId;
        }

        $email = strtolower(trim((string) ($user->email ?? '')));
        if (blank($email)) {
            return null;
        }

        $credentialDoctorId = DoctorCredential::query()
            ->whereRaw('LOWER(email) = ?', [$email], 'and')
            ->value('doctor_id');
        if (filled($credentialDoctorId)) {
            return (string) $credentialDoctorId;
        }

        $doctorIdByEmail = Doctor::query()
            ->whereRaw('LOWER(email) = ?', [$email], 'and')
            ->value('id');
        if (filled($doctorIdByEmail)) {
            session()->put('doctor_id', (string) $doctorIdByEmail);
            return (string) $doctorIdByEmail;
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

    private function getAuthUser()
    {
        return Auth::guard('filament')->user() ?? Auth::user();
    }

    public function render()
    {
        return view('livewire.doctor-admin.referral.add-referral');
    }
}
