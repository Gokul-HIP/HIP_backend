<?php

namespace App\Livewire\DoctorAdmin;

use App\Models\Referral;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $doctorId = $this->getDoctorId();

        return view('livewire.doctor-admin.dashboard', [
            'stats' => $this->getStats($doctorId),
            'trendBars' => $this->getTrendBars($doctorId),
            'recentReferrals' => $this->getRecentReferrals($doctorId),
        ]);
    }

    private function getStats(?int $doctorId): array
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

    private function getTrendBars(?int $doctorId): Collection
    {
        $months = collect(range(5, 0, -1))
            ->map(fn (int $monthsAgo) => now()->startOfMonth()->subMonths($monthsAgo))
            ->push(now()->startOfMonth());

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

    private function getRecentReferrals(?int $doctorId): Collection
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

    private function getDoctorId(): ?int
    {
        $doctorId = session('doctor_id');

        return $doctorId ? (int) $doctorId : null;
    }
}
