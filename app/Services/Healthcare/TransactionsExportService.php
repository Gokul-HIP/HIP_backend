<?php

namespace App\Services\Healthcare;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Transactions;
use Illuminate\Support\Collection;

class TransactionsExportService
{
    public function getCsvContent(HIPUser $user, array $filters = []): string
    {
        $hospitalIds = Hospital::query()
            ->where('organization_id', $user->organization_id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = Transactions::query()
            ->select('transactions.*')
            ->join('invoices', 'invoices.id', '=', 'transactions.invoice_id')
            ->join('healthinpocket_users as creators', 'creators.id', '=', 'invoices.created_by')
            ->join('hospitals', 'hospitals.id', '=', 'creators.hospital_id')
            ->with(['invoice.primaryPerson.hipUser', 'invoice.person.hipUser'])
            ->whereIn('hospitals.id', $hospitalIds)
            ->orderByDesc('transactions.created_at');

        if (($filters['hospital_filter'] ?? 'all') !== 'all') {
            $query->where('hospitals.id', (int) $filters['hospital_filter']);
        }

        if (($filters['status_filter'] ?? 'all') !== 'all') {
            $query->where('transactions.status', $filters['status_filter']);
        }

        if (($filters['service_type_filter'] ?? 'all') !== 'all') {
            $query->whereJsonContains('transactions.service_types', $filters['service_type_filter']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('transactions.created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('transactions.created_at', '<=', $filters['to_date']);
        }

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('transactions.id', 'like', '%' . $search . '%')
                    ->orWhereHas('invoice.person', function ($personQuery) use ($search) {
                        $personQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhereHas('hipUser', function ($hipUserQuery) use ($search) {
                                $hipUserQuery->where('hip_id', 'like', '%' . $search . '%');
                            });
                    })
                    ->orWhereHas('invoice.primaryPerson', function ($personQuery) use ($search) {
                        $personQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhereHas('hipUser', function ($hipUserQuery) use ($search) {
                                $hipUserQuery->where('hip_id', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $transactions = $query->get();

        $creators = HIPUser::query()
            ->whereIn('id', $transactions->pluck('invoice.created_by')->filter()->unique()->values())
            ->get(['id', 'hospital_id'])
            ->keyBy('id');

        $hospitals = Hospital::query()
            ->whereIn('id', $creators->pluck('hospital_id')->filter()->unique()->values())
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = $transactions
            ->map(fn (Transactions $transaction) => $this->mapTransactionToRow($transaction, $creators, $hospitals))
            ->all();

        return $this->buildCsvString($rows);
    }

    protected function mapTransactionToRow(Transactions $transaction, Collection $creators, Collection $hospitals): array
    {
        $invoice = $transaction->invoice;
        $member = $invoice?->primaryPerson ?: $invoice?->person;
        $creator = $creators->get((int) ($invoice?->created_by ?? 0));
        $hospital = $creator ? $hospitals->get((int) $creator->hospital_id) : null;

        $services = collect($transaction->service_types ?? [])->map(function ($type) {
            return match ($type) {
                'procedure' => 'Procedure',
                'labTest' => 'Diagnostic',
                'package' => 'Package',
                'pharmacy' => 'Pharmacy',
                default => ucfirst((string) $type),
            };
        })->implode(', ');

        return [
            'payment_id' => 'TXN-' . str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT),
            'member_name' => trim(($member?->first_name ?? '') . ' ' . ($member?->last_name ?? '')) ?: '-',
            'member_id' => $member?->hipUser?->hip_id ?: '-',
            'service_type' => $services ?: '-',
            'hospital_name' => $hospital?->name ?: '-',
            'amount' => number_format((float) ($transaction->total_amount ?? 0), 2, '.', ''),
            'payment_method' => $transaction->payment_method ?: '-',
            'status' => ucfirst((string) ($transaction->status ?? 'pending')),
            'date' => optional($transaction->created_at)?->format('Y-m-d H:i:s') ?: '',
        ];
    }

    protected function buildCsvString(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Payment ID',
            'Member Name',
            'Member ID',
            'Service Type',
            'Hospital',
            'Amount',
            'Payment Method',
            'Status',
            'Date',
        ]);

        foreach ($rows as $row) {
            fputcsv($out, [
                $row['payment_id'],
                $row['member_name'],
                $row['member_id'],
                $row['service_type'],
                $row['hospital_name'],
                $row['amount'],
                $row['payment_method'],
                $row['status'],
                $row['date'],
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }
}
