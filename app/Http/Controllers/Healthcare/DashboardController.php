<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\ProcedureBooking;
use App\Models\Transactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $orgId = Auth::user()?->organization_id;

        $pendingHospitals = collect();
        if ($orgId) {
            $pendingHospitals = Hospital::query()
                ->where('organization_id', $orgId)
                ->where('status', 'inactive')
                ->where(function ($q) {
                    $q->whereNull('onboarding_status')
                        ->orWhere('onboarding_status', 'draft');
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $hospitalIds = Hospital::query()
            ->where('organization_id', $orgId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
        $hospitalIdsArray = $hospitalIds->all();

        $txBase = Transactions::query()
            ->select('transactions.*')
            ->leftJoin('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->leftJoin('healthinpocket_users as invoice_creators', 'invoice_creators.id', '=', 'invoices.created_by')
            ->leftJoin('healthinpocket_users as tx_creators', 'tx_creators.id', '=', 'transactions.created_by')
            ->leftJoin('persons as invoice_persons', 'invoice_persons.id', '=', 'invoices.person_id')
            ->leftJoin('healthinpocket_users as member_users', 'member_users.id', '=', 'invoice_persons.hip_user_id')
            ->where(function ($q) use ($hospitalIds) {
                $q->whereIn('invoice_creators.hospital_id', $hospitalIds)
                    ->orWhereIn('tx_creators.hospital_id', $hospitalIds)
                    ->orWhereIn('member_users.hospital_id', $hospitalIds);
            })
            ->where(function ($q) use ($orgId) {
                $q->where('invoice_creators.organization_id', $orgId)
                    ->orWhere('tx_creators.organization_id', $orgId)
                    ->orWhere('member_users.organization_id', $orgId);
            });

        $completedBase = (clone $txBase)->where('transactions.status', 'completed');
        $refundedBase = (clone $txBase)->where('transactions.status', 'refunded');
        $coalescedDate = DB::raw('COALESCE(transactions.created_at, invoices.created_at)');

        $weekStart = now()->startOfWeek();
        $weekDays = collect(range(0, 6))->map(fn ($i) => $weekStart->copy()->addDays($i));
        $weeklyIncome = $weekDays->map(function ($day) use ($completedBase, $coalescedDate) {
            $amount = (clone $completedBase)
                ->whereDate($coalescedDate, $day->toDateString())
                ->sum('transactions.total_amount');
            return ['day' => strtoupper(substr($day->format('D'), 0, 3)), 'amount' => (float) $amount];
        });
        $weeklyMax = max(1, (float) $weeklyIncome->max('amount'));
        $weeklyBars = $weeklyIncome->map(function ($row) use ($weeklyMax) {
            $heightPct = max(4, ($row['amount'] / $weeklyMax) * 100);
            return [
                'day' => $row['day'],
                'amount' => $row['amount'],
                'heightPct' => number_format($heightPct, 1),
                'active' => $row['amount'] > 0 && $row['amount'] === $weeklyMax,
            ];
        });

        $months = collect(range(5, 1))->map(fn ($i) => now()->startOfMonth()->subMonths($i))
            ->push(now()->startOfMonth());
        $monthlyRevenue = $months->map(fn ($m) => (float) (clone $completedBase)
            ->whereYear($coalescedDate, $m->year)
            ->whereMonth($coalescedDate, $m->month)
            ->sum('transactions.total_amount'));
        $monthlyRefunds = $months->map(fn ($m) => (float) (clone $refundedBase)
            ->whereYear($coalescedDate, $m->year)
            ->whereMonth($coalescedDate, $m->month)
            ->sum('transactions.total_amount'));

        $appointmentsTrend = $months->map(fn ($m) => DoctorBooking::query()
            ->whereIn('hospital_id', $hospitalIdsArray)
            ->whereYear('created_at', $m->year)
            ->whereMonth('created_at', $m->month)
            ->count());
        $testsTrend = $months->map(fn ($m) => ProcedureBooking::query()
            ->whereIn('hospital_id', $hospitalIdsArray)
            ->whereYear('created_at', $m->year)
            ->whereMonth('created_at', $m->month)
            ->count());

        $quarterlyLabels = collect(range(3, 0))->map(fn ($i) => now()->subQuarters($i)->format('\QQ'))
            ->push(now()->format('\QQ'));
        $quarterlyGrowth = collect(range(3, 0))->map(function ($i) use ($completedBase, $coalescedDate) {
            $target = now()->subQuarters($i);
            return (clone $completedBase)
                ->whereYear($coalescedDate, $target->year)
                ->whereRaw('QUARTER(COALESCE(transactions.created_at, invoices.created_at)) = ?', [$target->quarter])
                ->count();
        })->push(
            (clone $completedBase)
                ->whereYear($coalescedDate, now()->year)
                ->whereRaw('QUARTER(COALESCE(transactions.created_at, invoices.created_at)) = ?', [now()->quarter])
                ->count()
        );

        $revChart = $this->buildLinePath($monthlyRevenue);
        $refundChart = $this->buildLinePath($monthlyRefunds);
        $apptChart = $this->buildLinePath($appointmentsTrend);
        $testChart = $this->buildLinePath($testsTrend);
        $growthChart = $this->buildLinePath($quarterlyGrowth, 420, 180, 12);

        $serviceRevenue = ['procedure' => 0.0, 'lab_test' => 0.0, 'pharmacy' => 0.0];
        foreach ((clone $completedBase)->get(['transactions.total_amount', 'transactions.service_types']) as $tx) {
            $types = collect((array) $tx->service_types)->filter()->values();
            if ($types->isEmpty()) {
                continue;
            }
            $split = ((float) $tx->total_amount) / max(1, $types->count());
            foreach ($types as $type) {
                $normalized = strtolower((string) $type);
                if ($normalized === 'labtest' || $normalized === 'package') {
                    $normalized = 'lab_test';
                }
                if (array_key_exists($normalized, $serviceRevenue)) {
                    $serviceRevenue[$normalized] += $split;
                }
            }
        }
        $serviceTotal = max(1, array_sum($serviceRevenue));
        $serviceCards = [
            ['label' => 'Hospital Services', 'key' => 'procedure', 'color' => '#0da2e7'],
            ['label' => 'Pharmacy Orders', 'key' => 'pharmacy', 'color' => '#f97316'],
            ['label' => 'Diagnostics', 'key' => 'lab_test', 'color' => '#8b5cf6'],
        ];
        $serviceBreakdown = collect($serviceCards)->map(function ($card) use ($serviceRevenue, $serviceTotal) {
            $amount = $serviceRevenue[$card['key']] ?? 0;
            $percent = ($amount / $serviceTotal) * 100;
            return [
                'label' => $card['label'],
                'color' => $card['color'],
                'amount' => $amount,
                'amount_formatted' => '₹' . number_format($amount, 0),
                'percent' => number_format($percent, 1),
            ];
        });

        $recentTransactionsRaw = (clone $txBase)
            ->with('invoice')
            ->orderByRaw('COALESCE(transactions.created_at, invoices.created_at) DESC')
            ->limit(4)
            ->get();
        $recentTransactions = $recentTransactionsRaw->map(function ($tx) {
            $serviceSummary = collect((array) $tx->service_types)
                ->filter()
                ->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))
                ->implode(', ');
            $status = strtolower((string) $tx->status);
            $badge = match ($status) {
                'completed' => 'background:#dcfce7;color:#15803d;',
                'pending' => 'background:#fef3c7;color:#d97706;',
                'refunded', 'failed', 'cancelled' => 'background:#fee2e2;color:#dc2626;',
                default => 'background:#f1f5f9;color:#475569;',
            };
            $txDate = $tx->created_at ?? optional($tx->invoice)->created_at;

            return [
                'id' => $tx->id,
                'service_summary' => $serviceSummary !== '' ? $serviceSummary : 'N/A',
                'amount' => number_format((float) $tx->total_amount, 2),
                'status' => ucfirst($status),
                'badge' => $badge,
                'date' => $txDate ? $txDate->format('M d, H:i') : '—',
            ];
        });

        $totalTxCount = (clone $txBase)->count();

        $now = now();
        $currentStart = $now->copy()->subDays(30)->startOfDay();
        $previousStart = $now->copy()->subDays(60)->startOfDay();
        $previousEnd = $now->copy()->subDays(31)->endOfDay();

        $dateScoped = fn ($q, $from, $to) => (clone $q)->whereBetween($coalescedDate, [$from, $to]);
        $kpiCards = [
            ['label' => 'Total Revenue', 'value' => $this->formatCurrencyCompact($dateScoped($completedBase, $currentStart, $now)->sum('transactions.total_amount')), 'delta' => $this->calcDeltaPct($dateScoped($completedBase, $currentStart, $now)->sum('transactions.total_amount'), $dateScoped($completedBase, $previousStart, $previousEnd)->sum('transactions.total_amount')), 'color' => 'blue'],
            ['label' => 'Appointments', 'value' => $this->formatCountCompact(DoctorBooking::query()->whereIn('hospital_id', $hospitalIdsArray)->whereBetween('created_at', [$currentStart, $now])->count()), 'delta' => $this->calcDeltaPct(DoctorBooking::query()->whereIn('hospital_id', $hospitalIdsArray)->whereBetween('created_at', [$currentStart, $now])->count(), DoctorBooking::query()->whereIn('hospital_id', $hospitalIdsArray)->whereBetween('created_at', [$previousStart, $previousEnd])->count()), 'color' => 'green'],
            ['label' => 'Diag. Tests', 'value' => $this->formatCountCompact(ProcedureBooking::query()->whereIn('hospital_id', $hospitalIdsArray)->whereBetween('created_at', [$currentStart, $now])->count()), 'delta' => $this->calcDeltaPct(ProcedureBooking::query()->whereIn('hospital_id', $hospitalIdsArray)->whereBetween('created_at', [$currentStart, $now])->count(), ProcedureBooking::query()->whereIn('hospital_id', $hospitalIdsArray)->whereBetween('created_at', [$previousStart, $previousEnd])->count()), 'color' => 'purple'],
            ['label' => 'Pharmacy', 'value' => $this->formatCountCompact($dateScoped($completedBase, $currentStart, $now)->whereJsonContains('transactions.service_types', 'pharmacy')->count()), 'delta' => $this->calcDeltaPct($dateScoped($completedBase, $currentStart, $now)->whereJsonContains('transactions.service_types', 'pharmacy')->count(), $dateScoped($completedBase, $previousStart, $previousEnd)->whereJsonContains('transactions.service_types', 'pharmacy')->count()), 'color' => 'amber'],
            ['label' => 'Members', 'value' => $this->formatCountCompact(HIPUser::query()->where('organization_id', $orgId)->count()), 'delta' => $this->calcDeltaPct(HIPUser::query()->where('organization_id', $orgId)->count(), HIPUser::query()->where('organization_id', $orgId)->where('created_at', '<=', $previousEnd)->count()), 'color' => 'teal'],
            ['label' => 'Refunds', 'value' => $this->formatCurrencyCompact($dateScoped($refundedBase, $currentStart, $now)->sum('transactions.total_amount')), 'delta' => $this->calcDeltaPct($dateScoped($refundedBase, $currentStart, $now)->sum('transactions.total_amount'), $dateScoped($refundedBase, $previousStart, $previousEnd)->sum('transactions.total_amount')), 'color' => 'rose'],
        ];

        return view('hospital-admin.dashboard', [
            'pendingHospitals' => $pendingHospitals,
            'alertCount' => $pendingHospitals->count(),
            'hasAlerts' => $pendingHospitals->isNotEmpty(),
            'kpiCards' => $kpiCards,
            'weeklyBars' => $weeklyBars,
            'months' => $months,
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyRefunds' => $monthlyRefunds,
            'quarterlyLabels' => $quarterlyLabels,
            'revChart' => $revChart,
            'refundChart' => $refundChart,
            'apptChart' => $apptChart,
            'testChart' => $testChart,
            'growthChart' => $growthChart,
            'serviceBreakdown' => $serviceBreakdown,
            'recentTransactions' => $recentTransactions,
            'totalTxCount' => $totalTxCount,
        ]);
    }

    private function buildLinePath(Collection $values, int $width = 420, int $height = 180, int $pad = 12): array
    {
        $values = $values->map(fn ($v) => (float) $v)->values();
        if ($values->isEmpty()) {
            return ['line' => '', 'area' => '', 'max' => 1.0];
        }

        $count = $values->count();
        $max = max(1.0, (float) $values->max());
        $usableW = $width - ($pad * 2);
        $usableH = $height - ($pad * 2);
        $stepX = $count > 1 ? ($usableW / ($count - 1)) : 0;

        $points = $values->map(function ($val, $idx) use ($pad, $stepX, $height, $usableH, $max) {
            $x = $pad + ($idx * $stepX);
            $y = $height - $pad - (($val / $max) * $usableH);
            return ['x' => round($x, 2), 'y' => round($y, 2)];
        })->values();

        $line = '';
        foreach ($points as $idx => $p) {
            $line .= ($idx === 0 ? 'M' : ' L') . $p['x'] . ',' . $p['y'];
        }

        $firstX = $points->first()['x'] ?? $pad;
        $lastX = $points->last()['x'] ?? ($width - $pad);
        $area = $line . ' L' . $lastX . ',' . ($height - $pad) . ' L' . $firstX . ',' . ($height - $pad) . ' Z';

        return ['line' => $line, 'area' => $area, 'max' => $max];
    }

    private function calcDeltaPct(float|int $current, float|int $previous): float
    {
        $current = (float) $current;
        $previous = (float) $previous;
        if ($previous <= 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    private function formatCountCompact(float|int $value): string
    {
        $value = (float) $value;
        if ($value >= 1000000) {
            return number_format($value / 1000000, 1) . 'M';
        }
        if ($value >= 1000) {
            return number_format($value / 1000, 1) . 'K';
        }
        return number_format($value, 0);
    }

    private function formatCurrencyCompact(float|int $value): string
    {
        $value = (float) $value;
        if ($value >= 1000000) {
            return '₹' . number_format($value / 1000000, 1) . 'M';
        }
        if ($value >= 1000) {
            return '₹' . number_format($value / 1000, 1) . 'K';
        }
        return '₹' . number_format($value, 0);
    }
}
