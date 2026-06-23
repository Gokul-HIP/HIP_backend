<?php

namespace App\Services\Cashier;

use App\Models\HIPUser;
use App\Models\Invoice;

class PaymentsExportService
{
    /**
     * Build CSV content for invoices created at the given hospital.
     */
    public function getCsvContent(?string $hospitalId = null): string
    {
        $invoices = Invoice::with(['creator', 'primaryPerson.hipUser', 'person.hipUser'])
            ->forHospital($hospitalId)
            ->latest()
            ->get();

        $rows = $invoices->map(fn (Invoice $inv) => $this->mapInvoiceToRow($inv))->all();

        return $this->buildCsvString($rows);
    }

    protected function mapInvoiceToRow(Invoice $invoice): array
    {
        $primary = $invoice->primaryPerson;
        $person  = $invoice->person;

        $memberName = $primary
            ? trim(($primary->first_name ?? '') . ' ' . ($primary->last_name ?? ''))
            : ($person ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) : '-');

        $memberId = $primary
            ? '#' . str_pad((string) $primary->id, 6, '0', STR_PAD_LEFT)
            : ($person ? '#' . str_pad((string) $person->id, 6, '0', STR_PAD_LEFT) : '—');

        $personName = $person
            ? trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? ''))
            : $memberName;

        $serviceTypes = $invoice->service_types ?? [];
        $serviceLabels = collect($serviceTypes)->map(function ($type) {
            return match ($type) {
                'procedure' => 'Procedure',
                'labTest'   => 'Diagnostic',
                'package'   => 'Package',
                'pharmacy'  => 'Pharmacy',
                default     => ucfirst((string) $type),
            };
        })->values()->all();

        $details  = $invoice->invoice_details ?? [];
        $itemized = [];

        $sumItems = function ($items) {
            return collect($items)->sum(function ($it) {
                $amount   = (float) ($it['amount'] ?? 0);
                $discount = isset($it['discount_amount']) && $it['discount_amount'] !== ''
                    ? (float) $it['discount_amount']
                    : null;
                return $discount ?? $amount;
            });
        };

        if (!empty($details['procedures']) && is_array($details['procedures'])) {
            $itemized[] = $sumItems($details['procedures']);
        }
        if (!empty($details['labTest']) && is_array($details['labTest'])) {
            $itemized[] = $sumItems($details['labTest']);
        }
        if (!empty($details['package']) && is_array($details['package'])) {
            $itemized[] = $sumItems($details['package']);
        }
        if (!empty($details['pharmacy']) && is_array($details['pharmacy'])) {
            $ph = $details['pharmacy'];
            $phAmount = isset($ph['discount_amount']) && $ph['discount_amount'] !== ''
                ? (float) $ph['discount_amount']
                : (float) ($ph['amount'] ?? 0);
            $itemized[] = $phAmount;
        }

        $total = (float) ($invoice->total_amount ?? $invoice->amount ?? array_sum($itemized));

        $createdInfo = $invoice->createdInfo();

        $createdAt = $invoice->created_at
            ? $invoice->created_at->format('d M, h:i A')
            : '';

        return [
            'id'             => $invoice->id,
            'member_name'    => $memberName,
            'member_id'      => $memberId,
            'person_name'    => $personName,
            'services'       => $serviceLabels,
            'itemized'       => $itemized,
            'total'          => $total,
            'payment_method' => $invoice->payment_method ?? '',
            'status'         => $invoice->status ?? '',
            'coins'          => (int) ($invoice->coins_earned ?? 0),
            'source'         => $createdInfo['label'],
            'created_by'     => $createdInfo['type'] === 'admin'
                ? ($createdInfo['creator'] ?? '—')
                : 'App Invoice',
            'created_at'     => $createdAt,
        ];
    }

    protected function buildCsvString(array $rows): string
    {
        $out = fopen('php://temp', 'r+');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

        fputcsv($out, [
            'Invoice ID',
            'Member Name',
            'Member ID',
            'Person Name',
            'Services',
            'Itemized Payable',
            'Total Amount',
            'Payment Method',
            'Status',
            'Source',
            'Coins Earned',
            'Created By',
            'Created At',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['member_name'],
                $r['member_id'],
                $r['person_name'],
                implode(', ', $r['services']),
                implode(', ', array_map(fn ($a) => number_format($a, 2), $r['itemized'])),
                number_format($r['total'], 2),
                $r['payment_method'],
                $r['status'],
                $r['source'],
                $r['coins'],
                $r['created_by'],
                $r['created_at'],
            ]);
        }

        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $csv;
    }
}

