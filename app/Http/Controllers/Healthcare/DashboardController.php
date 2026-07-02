<?php

namespace App\Http\Controllers\Healthcare;

use App\Http\Controllers\Controller;
use App\Models\DiagnosticTestBooking;
use App\Models\DoctorBooking;
use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\SecondOpinion;
use App\Models\Transactions;
use App\Support\TransactionReportHelper;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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

        $txBase = TransactionReportHelper::beginReportQuery();
        TransactionReportHelper::applyBookingJoins($txBase);
        TransactionReportHelper::applyHospitalIdsScope($txBase, $hospitalIdsArray);

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

        $appointmentsTrend = $months->map(function ($m) use ($hospitalIdsArray) {
            $doctorCount = DoctorBooking::query()
                ->whereIn('hospital_id', $hospitalIdsArray)
                ->whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->count();
            $secondOpinionCount = SecondOpinion::query()
                ->whereIn('branch_id', $hospitalIdsArray)
                ->whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->count();

            return $doctorCount + $secondOpinionCount;
        });
        $testsTrend = $months->map(fn ($m) => DiagnosticTestBooking::query()
            ->whereIn('branch_id', $hospitalIdsArray)
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

        $serviceRevenue = TransactionReportHelper::emptyDashboardRevenueBuckets();
        $categoryCounts = [
            'hospital_services' => 0,
            'pharmacy' => 0,
            'diagnostics' => 0,
        ];
        foreach ((clone $completedBase)->with('invoice')->get() as $tx) {
            $categories = TransactionReportHelper::resolveDashboardCategories($tx);
            if ($categories === []) {
                continue;
            }
            $split = TransactionReportHelper::transactionAmount($tx) / max(1, count($categories));
            foreach ($categories as $category) {
                if (! array_key_exists($category, $serviceRevenue)) {
                    continue;
                }
                $serviceRevenue[$category] += $split;
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
            }
        }
        $serviceTotal = max(1, array_sum($serviceRevenue));
        $serviceCards = [
            ['label' => 'Hospital Services', 'subtitle' => 'Doctor & Second Opinion', 'key' => 'hospital_services', 'color' => '#0da2e7'],
            ['label' => 'Pharmacy Orders', 'subtitle' => 'Pharmacy transactions', 'key' => 'pharmacy', 'color' => '#f97316'],
            ['label' => 'Diagnostics', 'subtitle' => 'Lab tests & packages', 'key' => 'diagnostics', 'color' => '#8b5cf6'],
        ];
        $serviceBreakdown = collect($serviceCards)->map(function ($card) use ($serviceRevenue, $serviceTotal, $categoryCounts) {
            $amount = $serviceRevenue[$card['key']] ?? 0;
            $percent = ($amount / $serviceTotal) * 100;
            return [
                'label' => $card['label'],
                'subtitle' => $card['subtitle'],
                'color' => $card['color'],
                'amount' => $amount,
                'amount_formatted' => '₹' . number_format($amount, 0),
                'percent' => number_format($percent, 1),
                'count' => $categoryCounts[$card['key']] ?? 0,
            ];
        });

        $recentTransactionsRaw = TransactionReportHelper::applyDefaultOrdering(
            (clone $txBase)->with(TransactionReportHelper::invoiceEagerLoads())
        )->limit(10)->get();

        $creatorIds = $recentTransactionsRaw
            ->flatMap(fn (Transactions $tx) => [
                (string) ($tx->invoice?->created_by ?? ''),
                (string) ($tx->created_by ?? ''),
            ])
            ->filter()
            ->unique()
            ->values();

        $creators = HIPUser::query()
            ->whereIn('id', $creatorIds)
            ->get(['id', 'hospital_id'])
            ->keyBy(fn (HIPUser $user) => (string) $user->id);

        $resolvedHospitalIds = $recentTransactionsRaw
            ->map(function (Transactions $transaction) use ($creators) {
                $invoice = $transaction->invoice;
                $creatorId = (string) ($invoice?->created_by ?? $transaction->created_by ?? '');
                $creator = $creatorId !== '' ? $creators->get($creatorId) : null;
                $creatorHospital = $creator ? Hospital::query()->find((int) $creator->hospital_id) : null;

                return TransactionReportHelper::resolveHospital($invoice, $creatorHospital)?->id;
            })
            ->merge($creators->pluck('hospital_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $hospitals = Hospital::query()
            ->whereIn('id', $resolvedHospitalIds)
            ->get(['id', 'name'])
            ->keyBy('id');

        $recentTransactions = $recentTransactionsRaw->map(
            fn (Transactions $tx) => $this->mapTransactionForDashboard($tx, $creators, $hospitals)
        );

        $totalTxCount = (clone $txBase)->count('transactions.id');

        $now = now();
        $currentStart = $now->copy()->subDays(30)->startOfDay();
        $previousStart = $now->copy()->subDays(60)->startOfDay();
        $previousEnd = $now->copy()->subDays(31)->endOfDay();

        $dateScoped = fn ($q, $from, $to) => (clone $q)->whereBetween($coalescedDate, [$from, $to]);

        $appointmentCount = fn ($from, $to) => DoctorBooking::query()
            ->whereIn('hospital_id', $hospitalIdsArray)
            ->whereBetween('created_at', [$from, $to])
            ->count()
            + SecondOpinion::query()
                ->whereIn('branch_id', $hospitalIdsArray)
                ->whereBetween('created_at', [$from, $to])
                ->count();

        $diagnosticCount = fn ($from, $to) => DiagnosticTestBooking::query()
            ->whereIn('branch_id', $hospitalIdsArray)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $pharmacyTxCount = fn ($from, $to) => (clone $txBase)
            ->where('transactions.status', 'completed')
            ->whereBetween($coalescedDate, [$from, $to])
            ->whereJsonContains('transactions.service_types', 'pharmacy')
            ->count('transactions.id');

        $kpiCards = [
            ['label' => 'Total Revenue', 'value' => $this->formatCurrencyCompact($dateScoped($completedBase, $currentStart, $now)->sum('transactions.total_amount')), 'delta' => $this->calcDeltaPct($dateScoped($completedBase, $currentStart, $now)->sum('transactions.total_amount'), $dateScoped($completedBase, $previousStart, $previousEnd)->sum('transactions.total_amount')), 'color' => 'blue'],
            ['label' => 'Appointments', 'value' => $this->formatCountCompact($appointmentCount($currentStart, $now)), 'delta' => $this->calcDeltaPct($appointmentCount($currentStart, $now), $appointmentCount($previousStart, $previousEnd)), 'color' => 'green'],
            ['label' => 'Diagnostics', 'value' => $this->formatCountCompact($diagnosticCount($currentStart, $now)), 'delta' => $this->calcDeltaPct($diagnosticCount($currentStart, $now), $diagnosticCount($previousStart, $previousEnd)), 'color' => 'purple'],
            ['label' => 'Pharmacy', 'value' => $this->formatCountCompact($pharmacyTxCount($currentStart, $now)), 'delta' => $this->calcDeltaPct($pharmacyTxCount($currentStart, $now), $pharmacyTxCount($previousStart, $previousEnd)), 'color' => 'amber'],
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

    private function mapTransactionForDashboard(
        Transactions $transaction,
        EloquentCollection $creators,
        EloquentCollection $hospitals
    ): array {
        $invoice = $transaction->invoice;
        $primary = $invoice?->primaryPerson;
        $person = $invoice?->person;
        $member = $primary ?: $person;
        $memberName = trim(($member?->first_name ?? '') . ' ' . ($member?->last_name ?? '')) ?: '—';

        $creatorId = (string) ($invoice?->created_by ?? $transaction->created_by ?? '');
        $creator = $creatorId !== '' ? $creators->get($creatorId) : null;
        $creatorHospital = $creator ? $hospitals->get((int) $creator->hospital_id) : null;
        $hospitalName = TransactionReportHelper::resolveHospitalName($invoice, $creatorHospital);

        $serviceLabels = collect($transaction->service_types ?? [])
            ->map(fn ($type) => TransactionReportHelper::serviceTypeLabel((string) $type))
            ->filter()
            ->values();

        if ($serviceLabels->isEmpty()) {
            if ($invoice?->doctor_booking_id) {
                $serviceLabels->push('Doctor Consultation');
            } elseif ($invoice?->second_opinion_id) {
                $serviceLabels->push('Second Opinion');
            } elseif ($invoice?->diagnostic_test_booking_id) {
                $serviceLabels->push('Diagnostic Package');
            }
        }

        $categories = collect(TransactionReportHelper::resolveDashboardCategories($transaction))
            ->map(fn ($category) => TransactionReportHelper::dashboardCategoryLabel($category))
            ->implode(', ');

        $status = strtolower((string) $transaction->status);
        $badge = match ($status) {
            'completed' => 'background:#dcfce7;color:#15803d;',
            'pending' => 'background:#fef3c7;color:#d97706;',
            'refunded', 'failed', 'cancelled' => 'background:#fee2e2;color:#dc2626;',
            default => 'background:#f1f5f9;color:#475569;',
        };
        $txDate = $transaction->created_at ?? $invoice?->created_at;

        return [
            'id' => $transaction->id,
            'payment_id' => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
            'member_name' => $memberName,
            'service_summary' => $serviceLabels->implode(', ') ?: '—',
            'category' => $categories !== '' ? $categories : '—',
            'hospital_name' => $hospitalName,
            'amount' => number_format(TransactionReportHelper::transactionAmount($transaction), 2),
            'payment_method' => $transaction->payment_method ?: '—',
            'status' => ucfirst($status),
            'badge' => $badge,
            'date' => $txDate ? $txDate->format('M d, H:i') : '—',
        ];
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
