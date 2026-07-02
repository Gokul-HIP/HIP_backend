<?php

namespace App\Http\Controllers\Cashier;

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
        $recentPayments = collect();
        $totalCount = 0;

        if ($hospitalId) {
            $baseQuery = Invoice::query()->forHospital($hospitalId);
            $totalCount = (clone $baseQuery)->count();

            $stats = [
                'total_transactions' => $totalCount,
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

            $recentPayments = (clone $baseQuery)
                ->with(['creator', 'primaryPerson.hipUser', 'person.hipUser'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Invoice $invoice) => $this->mapInvoiceToRow($invoice));
        }

        return view('cashier-admin.dashboard', [
            'stats' => $stats,
            'recentPayments' => $recentPayments,
            'totalCount' => $totalCount,
            'showingCount' => $recentPayments->count(),
        ]);
    }

    protected function mapInvoiceToRow(Invoice $invoice): array
    {
        $primary = $invoice->primaryPerson;
        $person = $invoice->person;

        $memberName = $primary
            ? trim(($primary->first_name ?? '') . ' ' . ($primary->last_name ?? ''))
            : ($person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : '—');

        $memberId = $primary?->hipUser?->hip_id
            ?? $person?->hipUser?->hip_id
            ?? ($primary ? '#' . str_pad((string) $primary->id, 6, '0', STR_PAD_LEFT) : '—');

        $serviceTypes = $invoice->service_types ?? [];
        $serviceLabels = collect($serviceTypes)->map(function ($type) {
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

        $total = (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
        $status = strtolower((string) ($invoice->status ?? 'pending'));

        return [
            'id' => $invoice->id,
            'payment_id' => 'INV-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT),
            'member_name' => $memberName !== '' ? $memberName : '—',
            'member_id' => $memberId,
            'services' => $serviceLabels,
            'service_summary' => $serviceLabels !== [] ? implode(', ', $serviceLabels) : '—',
            'total' => $total,
            'payment_method' => $invoice->payment_method ?: '—',
            'status' => $status,
            'status_label' => ucfirst($status),
            'created_at' => $invoice->created_at
                ? $invoice->created_at->format('d M, h:i A')
                : '—',
        ];
    }
}
