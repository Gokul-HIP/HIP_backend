<?php

namespace App\Http\Controllers\Pharmacist;

use App\Http\Controllers\Controller;
use App\Models\HIPUser;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;
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
            $baseQuery = $this->pharmacyInvoicesQuery($hospitalId);

            $stats = [
                'total_transactions' => (clone $baseQuery)->count(),
                'daily_revenue' => (float) (clone $baseQuery)
                    ->where('status', 'completed')
                    ->whereDate('created_at', today())
                    ->get()
                    ->sum(fn (Invoice $invoice) => $this->pharmacyAmount($invoice)),
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

        return view('pharmacist-admin.dashboard', [
            'stats' => $stats,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    protected function pharmacyInvoicesQuery(?string $hospitalId): Builder
    {
        return Invoice::query()
            ->forHospital($hospitalId)
            ->where(function (Builder $query) {
                $query->whereJsonContains('service_types', 'pharmacy')
                    ->orWhereNotNull('invoice_details->pharmacy');
            });
    }

    protected function pharmacyAmount(Invoice $invoice): float
    {
        $details = $invoice->invoice_details ?? [];

        if (! empty($details['pharmacy']) && is_array($details['pharmacy'])) {
            $pharmacy = $details['pharmacy'];

            if (isset($pharmacy['discount_amount']) && $pharmacy['discount_amount'] !== '') {
                return (float) $pharmacy['discount_amount'];
            }

            return (float) ($pharmacy['amount'] ?? 0);
        }

        return (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
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
            'amount' => $this->pharmacyAmount($invoice),
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
