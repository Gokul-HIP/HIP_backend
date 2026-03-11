<?php

namespace App\Livewire\DoctorAdmin\Referral;

use App\Models\Doctor;
use App\Models\DoctorAssignment;
use App\Models\Hospital;
use App\Models\Referral;
use Livewire\Component;
use Livewire\WithPagination;

class ReceiveReferral extends Component
{
    use WithPagination;

    public int $step = 1;
    public int $perPage = 10;

    public $hospital_id;
    public $referred_to_doctor_id;
    public $member_name = '';
    public $country_code = '+91';
    public $phone_number = '';
    public $referral_date = '';
    public $insurance_member_id = '';
    public $medical_notes = '';

    public function mount(): void
    {
        $this->referral_date = now()->format('Y-m-d');
    }

    public function updatedHospitalId($value): void
    {
        $this->referred_to_doctor_id = null;
    }

    public function nextStep(): void
    {
        $this->validateStepOne();
        $this->step = 2;
    }

    public function previousStep(): void
    {
        $this->step = 1;
    }

    public function saveDraft(): void
    {
        $this->validateStepOne();

        $this->persistReferral('draft');
    }

    public function createReferral(): void
    {
        $this->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'referred_to_doctor_id' => 'required|exists:doctors,id',
            'member_name' => 'required|string|max:255',
            'country_code' => 'required|string|max:10',
            'phone_number' => 'required|string|max:20',
            'referral_date' => 'required|date',
            'insurance_member_id' => 'nullable|string|max:100',
            'medical_notes' => 'required|string|min:10',
        ]);

        $this->persistReferral('submitted');
    }

    private function validateStepOne(): void
    {
        $this->validate([
            'hospital_id' => 'required|exists:hospitals,id',
            'referred_to_doctor_id' => 'required|exists:doctors,id',
        ]);
    }

    private function persistReferral(string $saveState): void
    {
        $doctorId = $this->getDoctorId();

        Referral::create([
            'referred_by_doctor_id' => $doctorId,
            'hospital_id' => $this->hospital_id,
            'referred_to_doctor_id' => $this->referred_to_doctor_id,
            'member_name' => $this->member_name ?: null,
            'country_code' => $this->country_code,
            'phone_number' => $this->phone_number ?: null,
            'referral_date' => $this->referral_date ?: null,
            'insurance_member_id' => $this->insurance_member_id ?: null,
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

        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset([
            'step',
            'hospital_id',
            'referred_to_doctor_id',
            'member_name',
            'country_code',
            'phone_number',
            'referral_date',
            'insurance_member_id',
            'medical_notes',
        ]);

        $this->step = 1;
        $this->country_code = '+91';
        $this->referral_date = now()->format('Y-m-d');
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function getHospitalsProperty()
    {
        $doctorId = $this->getDoctorId();

        if (!$doctorId) {
            return Hospital::orderBy('name')->get();
        }

        $doctor = Doctor::find($doctorId);
        $hospitalIds = $doctor?->hospital_ids ?? [];

        if (empty($hospitalIds)) {
            return Hospital::orderBy('name')->get();
        }

        return Hospital::whereIn('id', $hospitalIds)->orderBy('name')->get();
    }

    public function getDoctorsProperty()
    {
        if (!$this->hospital_id) {
            return collect();
        }

        $doctorId = $this->getDoctorId();

        $doctorIds = DoctorAssignment::where('hospital_id', $this->hospital_id)
            ->when($doctorId, fn ($query) => $query->where('doctor_id', '!=', $doctorId))
            ->where('status', 'active')
            ->distinct()
            ->pluck('doctor_id');

        if ($doctorIds->isEmpty()) {
            return Doctor::whereJsonContains('hospital_ids', (int) $this->hospital_id)
                ->when($doctorId, fn ($query) => $query->where('id', '!=', $doctorId))
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        return Doctor::whereIn('id', $doctorIds)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    private function getDoctorId(): ?int
    {
        $doctorId = session('doctor_id');

        return $doctorId ? (int) $doctorId : null;
    }

    public function getStatusCountsProperty(): array
    {
        $doctorId = $this->getDoctorId();

        if (!$doctorId) {
            return [
                'total' => 0,
                'pending' => 0,
                'accepted' => 0,
                'completed' => 0,
            ];
        }

        $counts = Referral::query()
            ->where('referred_to_doctor_id', $doctorId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            ")
            ->first();

        return [
            'total' => (int) ($counts?->total ?? 0),
            'pending' => (int) ($counts?->pending ?? 0),
            'accepted' => (int) ($counts?->accepted ?? 0),
            'completed' => (int) ($counts?->completed ?? 0),
        ];
    }

    public function render()
    {
        $doctorId = $this->getDoctorId();

        $referrals = Referral::query()
            ->with(['member:id,hip_id', 'referredByDoctor:id,name', 'hospital:id,name'])
            ->when(
                $doctorId,
                fn ($query) => $query->where('referred_to_doctor_id', $doctorId),
                fn ($query) => $query->whereRaw('1 = 0')
            )
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.doctor-admin.referral.receive-referral', [
            'referrals' => $referrals,
            'statusCounts' => $this->statusCounts,
        ]);
    }
}
