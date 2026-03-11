<?php

namespace App\Livewire\DoctorAdmin\Referral;

use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\Hospital;
use App\Models\Referral;
use App\Services\Referral\ReferralMemberResolver;
use Livewire\Component;

class AddReferral extends Component
{
    public int $step = 1;

    public $hospital_id;
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

    public function updatedHospitalId(): void
    {
        $this->doctor_id = null;
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

    public function getHospitalsProperty()
    {
        return Hospital::orderBy('name')->get();
    }

    public function getDoctorsProperty()
    {
        if (!$this->hospital_id) {
            return collect();
        }

        $currentDoctorId = $this->getCurrentDoctorId();

        $doctorIds = DoctorAssignment::where('hospital_id', $this->hospital_id)
            ->when($currentDoctorId, fn ($query) => $query->where('doctor_id', '!=', $currentDoctorId))
            ->distinct()
            ->pluck('doctor_id');

        return Doctor::whereIn('id', $doctorIds)
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
            'hospital_id' => 'required|exists:hospitals,id',
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
        $this->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'doctor_id' => 'required|exists:doctors,id',
        ]);

        $this->createReferral('draft');
        $this->redirectRoute('doctor.referral.send');
    }

    public function submit(): void
    {
        $this->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'doctor_id' => 'required|exists:doctors,id',
            'member_name' => 'required|string|max:255',
            'phone_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
            'referral_date' => 'required|date',
            'member_id' => 'nullable|string|max:100',
            'medical_notes' => 'required|string|min:10',
        ]);

        $this->createReferral('submitted');
        $this->redirectRoute('doctor.referral.send');
    }

    private function createReferral(string $saveState): void
    {
        $resolved = $this->resolveMember();

        Referral::create([
            'referred_by_doctor_id' => $this->getCurrentDoctorId(),
            'hospital_id' => $this->hospital_id,
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

        $this->dispatch(
            'toast',
            type: 'success',
            message: $saveState === 'draft'
                ? 'Referral draft saved successfully.'
                : 'Referral created successfully.'
        );

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

    private function getCurrentDoctorId(): ?int
    {
        $doctorId = session('doctor_id');

        return $doctorId ? (int) $doctorId : null;
    }

    public function render()
    {
        return view('livewire.doctor-admin.referral.add-referral');
    }
}
