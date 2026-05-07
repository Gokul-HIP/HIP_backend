<?php

namespace App\Livewire\DoctorAdmin;

use App\Models\Doctor;
use App\Models\DoctorCredential;
use App\Models\Referral;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Dashboard extends Component
{
    public string $trendRange = '6m';

    public function render()
    {
        $doctorId = $this->getDoctorId();

        return view('livewire.doctor-admin.dashboard', [
            'stats' => $this->getStats($doctorId),
            'trendBars' => $this->getTrendBars($doctorId),
            'trendRangeLabel' => $this->getTrendRangeLabel(),
            'recentReferrals' => $this->getRecentReferrals($doctorId),
        ]);
    }

    public function updatedTrendRange(string $value): void
    {
        if (!in_array($value, ['latest', '3m', '6m'], true)) {
            $this->trendRange = '6m';
        }
    }

    private function getStats(?string $doctorId): array
    {
        if (!$doctorId) {
            return [
                'outgoing' => 0,
                'received' => 0,
                'pending_actions' => 0,
                'active_members' => 0,
            ];
        }

        $outgoing = Referral::query()
            ->where('referred_by_doctor_id', $doctorId)
            ->count();

        $received = Referral::query()
            ->where('referred_to_doctor_id', $doctorId)
            ->count();

        $pendingActions = Referral::query()
            ->where('referred_to_doctor_id', $doctorId)
            ->where('status', 'pending')
            ->count();

        $activeMembers = Referral::query()
            ->where(function ($query) use ($doctorId) {
                $query->where('referred_by_doctor_id', $doctorId)
                    ->orWhere('referred_to_doctor_id', $doctorId);
            })
            ->selectRaw(
                "COALESCE(CAST(member_user_id AS CHAR), NULLIF(insurance_member_id, ''), NULLIF(phone_number, '')) as member_key"
            )
            ->pluck('member_key')
            ->filter()
            ->unique()
            ->count();

        return [
            'outgoing' => $outgoing,
            'received' => $received,
            'pending_actions' => $pendingActions,
            'active_members' => $activeMembers,
        ];
    }

    private function getTrendBars(?string $doctorId): Collection
    {
        $monthsBack = match ($this->trendRange) {
            'latest' => 0,
            '3m' => 2,
            default => 5,
        };

        $months = collect(range($monthsBack, 0, -1))
            ->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo));

        if (!$doctorId) {
            return $months->map(fn (Carbon $month) => [
                'label' => strtoupper($month->format('M')),
                'count' => 0,
                'pct' => 0,
            ]);
        }

        $counts = Referral::query()
            ->where('referred_by_doctor_id', $doctorId)
            ->whereDate('created_at', '>=', $months->first()->copy()->startOfMonth())
            ->selectRaw('YEAR(created_at) as year, MONTH(created_at) as month, COUNT(*) as total')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(fn ($item) => sprintf('%04d-%02d', $item->year, $item->month));

        $maxCount = max(1, (int) $counts->max('total'));

        return $months->map(function (Carbon $month) use ($counts, $maxCount) {
            $key = $month->format('Y-m');
            $count = (int) ($counts[$key]->total ?? 0);

            return [
                'label' => strtoupper($month->format('M')),
                'count' => $count,
                'pct' => $count > 0 ? max(10, (int) round(($count / $maxCount) * 100)) : 0,
            ];
        });
    }

    private function getTrendRangeLabel(): string
    {
        return match ($this->trendRange) {
            'latest' => 'Latest',
            '3m' => 'Last 3 Months',
            default => 'Last 6 Months',
        };
    }

    private function getRecentReferrals(?string $doctorId): Collection
    {
        if (!$doctorId) {
            return collect();
        }

        return Referral::query()
            ->with([
                'member:id,hip_id',
                'hospital:id,name',
            ])
            ->where(function ($query) use ($doctorId) {
                $query->where('referred_by_doctor_id', $doctorId)
                    ->orWhere('referred_to_doctor_id', $doctorId);
            })
            ->latest()
            ->limit(6)
            ->get()
            ->map(function (Referral $referral) use ($doctorId) {
                $status = strtolower((string) $referral->status);

                return [
                    'id' => $referral->id,
                    'member_name' => $referral->member_name ?: '-',
                    'member_id' => $referral->member?->hip_id ?? $referral->insurance_member_id ?? '-',
                    'referral_date' => optional($referral->referral_date)->format('d-m-Y') ?? '-',
                    'hospital_name' => $referral->hospital?->name ?? '-',
                    'status' => $status,
                    'status_label' => match ($status) {
                        'accepted' => 'Confirmed',
                        'completed' => 'Completed',
                        'rejected' => 'Cancelled',
                        default => 'Pending',
                    },
                    'direction' => $referral->referred_by_doctor_id === $doctorId ? 'sent' : 'received',
                ];
            });
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
