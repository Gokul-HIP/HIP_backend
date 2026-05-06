<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Hospital;
use App\Models\Diagnostic;
use App\Models\Pharmacy;
use App\Models\Doctor;
use App\Models\Transactions;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    
    public function index(Request $request){

        $organizations = Organization::where('status', 'active')->count();
        $hospitals = Hospital::where('status', 'active')->count();
        $diagnosticCenters = Diagnostic::where('status', 'active')->count();
        $pharmacies = Pharmacy::where('status', 'active')->count();
        $doctors = Doctor::where('status', 'active')->count();

        $range = (string) $request->query('range', '6m');
        $weeks = match ($range) {
            '1m' => 4,
            '3m' => 12,
            default => 24,
        };

        $rangeLabel = match ($range) {
            '1m' => 'Last month',
            '3m' => 'Last 3 months',
            default => 'Last 6 months',
        };

        $endDate = Carbon::now()->endOfWeek();
        $startDate = Carbon::now()->subWeeks($weeks - 1)->startOfWeek();

        $transactions = Transactions::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(['created_at', 'transaction_amount']);

        $incomeBuckets = [];
        $countBuckets = [];
        $labels = [];

        for ($i = 0; $i < $weeks; $i++) {
            $weekStart = (clone $startDate)->addWeeks($i)->startOfWeek();
            $weekKey = $weekStart->format('o-W');
            $labels[] = 'W' . ($i + 1);
            $incomeBuckets[$weekKey] = 0.0;
            $countBuckets[$weekKey] = 0;
        }

        foreach ($transactions as $transaction) {
            $weekKey = Carbon::parse($transaction->created_at)->startOfWeek()->format('o-W');
            if (array_key_exists($weekKey, $incomeBuckets)) {
                $incomeBuckets[$weekKey] += (float) ($transaction->transaction_amount ?? 0);
                $countBuckets[$weekKey]++;
            }
        }

        $incomeChartData = array_values(array_map(
            fn ($value) => round((float) $value, 2),
            $incomeBuckets
        ));
        $transactionChartData = array_values($countBuckets);

        $stats = [
            'total_turnover' => round(array_sum($incomeChartData), 2),
            'total_transactions' => array_sum($transactionChartData),
        ];

        return view('admin.dashboard', compact(
            'organizations',
            'hospitals',
            'diagnosticCenters',
            'pharmacies',
            'doctors',
            'stats',
            'labels',
            'incomeChartData',
            'transactionChartData',
            'range',
            'rangeLabel'
        ));
        
    }

}
