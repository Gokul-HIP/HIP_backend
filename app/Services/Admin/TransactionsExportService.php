<?php

namespace App\Services\Admin;

use App\Models\HIPUser;
use App\Models\Hospital;
use App\Models\Organization;
use App\Models\Transactions;
use App\Support\TransactionReportHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TransactionsExportService
{
    public function getCsvContent(array $filters = []): string
    {
        $query = TransactionReportHelper::beginReportQuery();

        TransactionReportHelper::applyBookingJoins($query)
            ->leftJoin('organizations', 'organizations.id', '=', 'cashier_hospitals.organization_id')
            ->with(TransactionReportHelper::invoiceEagerLoads());

        TransactionReportHelper::applyDefaultOrdering($query);

        if (($filters['organization_filter'] ?? 'all') !== 'all') {
            $organizationHospitalIds = Hospital::query()
                ->where('organization_id', (int) $filters['organization_filter'])
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            TransactionReportHelper::applyHospitalIdsScope($query, $organizationHospitalIds);
        }

        if (($filters['hospital_filter'] ?? 'all') !== 'all') {
            TransactionReportHelper::applyHospitalIdsScope($query, [(int) $filters['hospital_filter']]);
        }

        if (($filters['status_filter'] ?? 'all') !== 'all') {
            $query->where('transactions.status', $filters['status_filter']);
        }

        TransactionReportHelper::applyServiceTypeFilter($query, (string) ($filters['service_type_filter'] ?? 'all'));

        TransactionReportHelper::applyDateRangeFilter(
            $query,
            (string) ($filters['from_date'] ?? ''),
            (string) ($filters['to_date'] ?? '')
        );

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('organizations.name', 'like', '%' . $search . '%')
                    ->orWhere('cashier_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('doctor_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('doctor_branches.name', 'like', '%' . $search . '%')
                    ->orWhere('second_opinion_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('diagnostic_hospitals.name', 'like', '%' . $search . '%')
                    ->orWhere('transactions.id', 'like', '%' . $search . '%')
                    ->orWhereHas('invoice.person', function (Builder $personQuery) use ($search) {
                        $personQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhereHas('hipUser', function (Builder $hipUserQuery) use ($search) {
                                $hipUserQuery->where('hip_id', 'like', '%' . $search . '%');
                            });
                    })
                    ->orWhereHas('invoice.primaryPerson', function (Builder $personQuery) use ($search) {
                        $personQuery
                            ->where('first_name', 'like', '%' . $search . '%')
                            ->orWhere('last_name', 'like', '%' . $search . '%')
                            ->orWhere('mobile', 'like', '%' . $search . '%')
                            ->orWhereHas('hipUser', function (Builder $hipUserQuery) use ($search) {
                                $hipUserQuery->where('hip_id', 'like', '%' . $search . '%');
                            });
                    });
            });
        }

        $transactions = $query->get();

        $creatorIds = $transactions
            ->flatMap(fn (Transactions $transaction) => [
                (string) ($transaction->invoice?->created_by ?? ''),
                (string) ($transaction->created_by ?? ''),
            ])
            ->filter()
            ->unique()
            ->values();

        $creators = HIPUser::query()
            ->whereIn('id', $creatorIds)
            ->get(['id', 'hospital_id'])
            ->keyBy(fn (HIPUser $user) => (string) $user->id);

        $resolvedHospitalIds = $transactions
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
            ->get(['id', 'name', 'organization_id'])
            ->keyBy('id');

        $organizations = Organization::query()
            ->whereIn('id', $hospitals->pluck('organization_id')->filter()->unique()->values())
            ->get(['id', 'name'])
            ->keyBy('id');

        $rows = $transactions
            ->map(fn (Transactions $transaction) => $this->mapTransactionToRow($transaction, $creators, $hospitals, $organizations))
            ->all();

        return $this->buildCsvString($rows);
    }

    protected function mapTransactionToRow(
        Transactions $transaction,
        Collection $creators,
        Collection $hospitals,
        Collection $organizations
    ): array {
        $invoice = $transaction->invoice;
        $member = $invoice?->primaryPerson ?: $invoice?->person;
        $creatorId = (string) ($invoice?->created_by ?? $transaction->created_by ?? '');
        $creator = $creatorId !== '' ? $creators->get($creatorId) : null;
        $creatorHospital = $creator ? $hospitals->get((int) $creator->hospital_id) : null;
        $hospital = TransactionReportHelper::resolveHospital($invoice, $creatorHospital);
        $organization = $hospital ? $organizations->get((int) $hospital->organization_id) : null;

        $services = collect($transaction->service_types ?? [])
            ->map(fn ($type) => TransactionReportHelper::serviceTypeLabel((string) $type))
            ->implode(', ');

        return [
            'payment_id' => $this->formatPaymentId($transaction->id),
            'member_name' => trim(($member?->first_name ?? '') . ' ' . ($member?->last_name ?? '')) ?: '-',
            'member_id' => $member?->hipUser?->hip_id ?: '-',
            'service_type' => $services ?: '-',
            'organization_name' => $organization?->name ?: '-',
            'hospital_name' => $hospital?->name ?: '-',
            'amount' => number_format(TransactionReportHelper::transactionAmount($transaction), 2, '.', ''),
            'payment_method' => $transaction->payment_method ?: '-',
            'status' => ucfirst((string) ($transaction->status ?? 'pending')),
            'date' => optional($transaction->created_at ?? $invoice?->created_at)?->format('Y-m-d H:i:s') ?: '',
        ];
    }

    protected function formatPaymentId(string $transactionId): string
    {
        return is_numeric($transactionId)
            ? 'TXN-' . str_pad($transactionId, 8, '0', STR_PAD_LEFT)
            : 'TXN-' . strtoupper(substr($transactionId, 0, 8));
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
            'Organization',
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
                $row['organization_name'],
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
