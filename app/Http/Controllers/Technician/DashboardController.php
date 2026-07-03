<?php

namespace App\Http\Controllers\Technician;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use App\Models\Invoice;
use App\Support\TechnicianDiagnosticInvoiceHelper;
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
        ];
        $recentTransactions = collect();

        if ($hospitalId) {
            $baseQuery = TechnicianDiagnosticInvoiceHelper::baseQuery($hospitalId);

            $stats = [
                'total_transactions' => (clone $baseQuery)->count(),
                'daily_revenue' => (float) (clone $baseQuery)
                    ->where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->get()
                    ->sum(fn (Invoice $invoice) => TechnicianDiagnosticInvoiceHelper::diagnosticAmount($invoice)),
                'pending_invoices' => (clone $baseQuery)
                    ->where('status', 'pending')
                    ->count(),
            ];

            $recentTransactions = (clone $baseQuery)
                ->with(['primaryPerson', 'person'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (Invoice $invoice) => $this->mapRecentTransaction($invoice));
        }

        return view('technician-admin.dashboard', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    protected function mapRecentTransaction(Invoice $invoice): array
    {
        $primary = $invoice->primaryPerson;
        $person = $invoice->person;

        $patientName = $primary
            ? trim(($primary->first_name ?? '') . ' ' . ($primary->last_name ?? ''))
            : ($person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : '—');

        $status = strtolower((string) ($invoice->status ?? 'pending'));

        return [
            'invoice_id' => 'INV-' . str_pad((string) $invoice->id, 8, '0', STR_PAD_LEFT),
            'patient_name' => $patientName !== '' ? $patientName : '—',
            'service_summary' => implode(', ', TechnicianDiagnosticInvoiceHelper::serviceLabels($invoice)) ?: 'Diagnostic',
            'amount' => TechnicianDiagnosticInvoiceHelper::diagnosticAmount($invoice),
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
