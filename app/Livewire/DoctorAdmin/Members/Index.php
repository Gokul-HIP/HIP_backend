<?php

namespace App\Livewire\DoctorAdmin\Members;

use App\Models\Doctor;
use App\Models\DoctorCredential;
use App\Models\Referral;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $hospitalFilter = 'all';
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingHospitalFilter(): void
    {
        $this->resetPage();
    }

    public function getAvailableHospitalsProperty(): Collection
    {
        return $this->baseReferralQuery()
            ->with('hospital:id,name')
            ->get()
            ->pluck('hospital')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    }

    public function getStatusCountsProperty(): array
    {
        $patients = $this->patientCollection();

        return [
            'pending' => $patients->where('status', 'pending')->count(),
            'accepted' => $patients->where('status', 'accepted')->count(),
            'completed' => $patients->where('status', 'completed')->count(),
            'rejected' => $patients->where('status', 'rejected')->count(),
        ];
    }

    public function render()
    {
        $patients = $this->filteredPatients();
        $page = $this->getPage();
        $total = $patients->count();
        $items = $patients->forPage($page, $this->perPage)->values();

        $pagination = new LengthAwarePaginator(
            $items,
            $total,
            $this->perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        return view('livewire.doctor-admin.members.index', [
            'patients' => $pagination,
            'statusCounts' => $this->statusCounts,
            'availableHospitals' => $this->availableHospitals,
        ]);
    }

    private function filteredPatients(): Collection
    {
        return $this->patientCollection()
            ->when($this->search !== '', function (Collection $patients) {
                $search = mb_strtolower(trim($this->search));

                return $patients->filter(function (array $patient) use ($search) {
                    return str_contains(mb_strtolower($patient['member_name']), $search)
                        || str_contains(mb_strtolower($patient['member_id']), $search)
                        || str_contains(mb_strtolower($patient['phone']), $search);
                });
            })
            ->when($this->statusFilter !== 'all', fn (Collection $patients) => $patients->where('status', $this->statusFilter))
            ->when($this->hospitalFilter !== 'all', fn (Collection $patients) => $patients->where('hospital_id', (int) $this->hospitalFilter))
            ->values();
    }

    private function patientCollection(): Collection
    {
        return $this->baseReferralQuery()
            ->with([
                'member:id,hip_id,first_name,last_name,mobile_num',
                'hospital:id,name',
                'referredByDoctor:id,name',
            ])
            ->latest()
            ->get()
            ->groupBy(function (Referral $referral) {
                return $referral->member_user_id
                    ?: $referral->insurance_member_id
                    ?: $referral->phone_number
                    ?: 'referral-' . $referral->id;
            })
            ->map(function (Collection $referrals) {
                $latest = $referrals->sortByDesc('created_at')->first();

                return [
                    'id' => $latest->id,
                    'member_name' => $latest->member?->full_name ?: ($latest->member_name ?: '-'),
                    'member_id' => $latest->member?->hip_id ?: ($latest->insurance_member_id ?: '-'),
                    'phone' => $latest->member?->mobile_num ?: trim(($latest->country_code ?? '') . ' ' . ($latest->phone_number ?? '')),
                    'referral_date' => optional($latest->referral_date)->format('M d, Y') ?? '-',
                    'referred_by' => $latest->referredByDoctor?->name ?? '-',
                    'hospital_name' => $latest->hospital?->name ?? '-',
                    'hospital_id' => $latest->hospital_id,
                    'status' => strtolower((string) $latest->status),
                    'status_label' => match (strtolower((string) $latest->status)) {
                        'accepted' => 'Accepted',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        default => 'Pending',
                    },
                ];
            })
            ->sortByDesc(fn (array $patient) => $patient['id'])
            ->values();
    }

    private function baseReferralQuery()
    {
        $doctorId = $this->getDoctorId();

        return Referral::query()
            ->when(
                $doctorId,
                fn ($query) => $query->where(function ($inner) use ($doctorId) {
                    $inner->where('referred_by_doctor_id', $doctorId)
                        ->orWhere('referred_to_doctor_id', $doctorId);
                }),
                fn ($query) => $query->whereRaw('1 = 0')
            );
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
}
