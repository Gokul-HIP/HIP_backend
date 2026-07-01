<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Hospital;
use App\Models\Diagnostic;
use App\Models\Pharmacy;
use App\Models\Doctor;
use App\Models\DoctorReview;
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

        $recentReviews = DoctorReview::query()
            ->with(['member', 'doctor.organization'])
            ->latest()
            ->paginate(10, ['*'], 'reviews_page')
            ->withQueryString()
            ->through(function (DoctorReview $review) {
                $member = $review->member;
                $author = $member
                    ? trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''))
                    : '';

                if ($author === '') {
                    $author = 'Member';
                }

                $doctor = $review->doctor;
                $doctorName = $doctor?->name ? 'Dr. ' . $doctor->name : 'Doctor';
                $organization = $doctor?->organization?->name;

                return [
                    'id'           => $review->id,
                    'author'       => $author,
                    'organization' => $organization ? "{$doctorName} · {$organization}" : $doctorName,
                    'content'      => $review->displayComment() ?? 'No comment provided',
                    'rating'       => max(0, min(5, (int) ($review->rating ?? 0))),
                    'status'       => $review->status,
                ];
            });

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
            'rangeLabel',
            'recentReviews'
        ));
        
    }

    public function approveDoctorReview(Request $request, DoctorReview $review)
    {
        $review->update(['status' => 'active']);

        return redirect()
            ->route('admin.dashboard.index', $this->dashboardReviewRedirectParams($request))
            ->with('success', 'Doctor review approved successfully.');
    }

    public function rejectDoctorReview(Request $request, DoctorReview $review)
    {
        $review->update(['status' => 'inactive']);

        return redirect()
            ->route('admin.dashboard.index', $this->dashboardReviewRedirectParams($request))
            ->with('success', 'Doctor review rejected and marked inactive.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardReviewRedirectParams(Request $request): array
    {
        return array_filter([
            'reviews_page' => $request->input('reviews_page'),
            'range'        => $request->input('range'),
        ], fn ($value) => $value !== null && $value !== '');
    }
}
