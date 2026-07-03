<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        $hospitalId = $user instanceof HIPUser ? $user->hospital_id : null;

        $stats = [
            'total_transactions' => 0,
            'daily_revenue' => 0.0,
            'pending_invoices' => 0,
            'active_members' => 0,
        ];
        $recentTransactions = collect();

        if ($hospitalId) {
            $baseQuery = Invoice::query()->forHospital($hospitalId);

            $stats = [
                'total_transactions' => (clone $baseQuery)->count(),
                'daily_revenue' => (float) (clone $baseQuery)
                    ->where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->sum('total_amount'),
                'pending_invoices' => (clone $baseQuery)
                    ->where('status', 'pending')
                    ->count(),
                'active_members' => (int) ((clone $baseQuery)
                    ->where('status', 'completed')
                    ->selectRaw('COUNT(DISTINCT COALESCE(person_id, primary_person_id)) as members_count')
                    ->value('members_count') ?? 0),
            ];

            $recentTransactions = (clone $baseQuery)
                ->with(['primaryPerson', 'person'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Invoice $invoice) => $this->mapRecentTransaction($invoice));
        }

        return view('receptionist-admin.dashboard', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    protected function mapRecentTransaction(Invoice $invoice): array
    {
        $primary = $invoice->primaryPerson;
        $person = $invoice->person;

        $memberName = $primary
            ? trim(($primary->first_name ?? '') . ' ' . ($primary->last_name ?? ''))
            : ($person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : '—');

        $serviceLabels = collect($invoice->service_types ?? [])->map(function ($type) {
            return match ($type) {
                'procedure' => 'Procedure',
                'lab_test', 'labTest' => 'Diagnostic',
                'package' => 'Package',
                'pharmacy' => 'Pharmacy',
                'doctor_consultation' => 'Doctor Consultation',
                'second_opinion' => 'Second Opinion',
                'diagnostic_package' => 'Diagnostic Package',
                default => ucfirst(str_replace('_', ' ', (string) $type)),
            };
        })->filter()->values()->all();

        if ($serviceLabels === []) {
            if ($invoice->doctor_booking_id) {
                $serviceLabels[] = 'Doctor Consultation';
            } elseif ($invoice->second_opinion_id) {
                $serviceLabels[] = 'Second Opinion';
            } elseif ($invoice->diagnostic_test_booking_id) {
                $serviceLabels[] = 'Diagnostic Package';
            }
        }

        $status = strtolower((string) ($invoice->status ?? 'pending'));

        return [
            'invoice_id' => 'INV-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT),
            'patient_name' => $memberName !== '' ? $memberName : '—',
            'service_summary' => $serviceLabels !== [] ? implode(', ', $serviceLabels) : '—',
            'amount' => (float) ($invoice->total_amount ?? $invoice->amount ?? 0),
            'created_at' => $invoice->created_at,
            'status' => $status,
            'status_label' => ucfirst($status),
            'badge_class' => match ($status) {
                'completed' => 'paid',
                'pending' => 'pending',
                'cancelled', 'failed', 'refunded' => 'cancelled',
                default => 'pending',
            },
        ];
    }
}
