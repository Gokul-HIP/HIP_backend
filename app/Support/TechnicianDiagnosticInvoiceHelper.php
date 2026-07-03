<?php

namespace App\Support;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Builder;

class TechnicianDiagnosticInvoiceHelper
{
    /**
     * Invoices that belong on the technician diagnostic dashboard / payments list.
     *
     * Includes app bookings (diagnostic_package), cashier/technician lab_test & package lines.
     */
    public static function baseQuery(?string $hospitalId): Builder
    {
        return Invoice::query()
            ->forHospital($hospitalId)
            ->where(function (Builder $query) {
                $query->whereNotNull('diagnostic_test_booking_id')
                    ->orWhereJsonContains('service_types', 'diagnostic_package')
                    ->orWhereJsonContains('service_types', 'lab_test')
                    ->orWhereJsonContains('service_types', 'labTest')
                    ->orWhereJsonContains('service_types', 'package')
                    ->orWhereNotNull('invoice_details->lab_test')
                    ->orWhereNotNull('invoice_details->labTest')
                    ->orWhereNotNull('invoice_details->package');
            });
    }

    /**
     * @return list<string>
     */
    public static function serviceLabels(Invoice $invoice): array
    {
        $details = $invoice->invoice_details ?? [];
        $types = collect($invoice->service_types ?? [])
            ->map(fn ($type) => strtolower(str_replace('-', '_', trim((string) $type))))
            ->values();

        $labels = [];

        if ($invoice->diagnostic_test_booking_id || $types->contains('diagnostic_package')) {
            $labels[] = 'Diagnostic Package';
        }

        if (
            $types->contains('lab_test')
            || $types->contains('labtest')
            || ! empty($details['lab_test'])
            || ! empty($details['labTest'])
        ) {
            $labels[] = 'Lab Test';
        }

        if ($types->contains('package') || ! empty($details['package'])) {
            $labels[] = 'Package';
        }

        return array_values(array_unique($labels));
    }

    /**
     * @return list<float>
     */
    public static function itemizedAmounts(Invoice $invoice): array
    {
        $details = $invoice->invoice_details ?? [];
        $itemized = [];

        foreach (['lab_test', 'labTest'] as $key) {
            if (! empty($details[$key]) && is_array($details[$key])) {
                $itemized[] = self::sumLineItems($details[$key]);
            }
        }

        if (! empty($details['package']) && is_array($details['package'])) {
            $itemized[] = self::sumLineItems($details['package']);
        }

        return $itemized;
    }

    public static function diagnosticAmount(Invoice $invoice): float
    {
        $itemized = self::itemizedAmounts($invoice);

        if ($itemized !== []) {
            return array_sum($itemized);
        }

        $types = collect($invoice->service_types ?? [])
            ->map(fn ($type) => strtolower(str_replace('-', '_', trim((string) $type))));

        if ($invoice->diagnostic_test_booking_id || $types->contains('diagnostic_package')) {
            return (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
        }

        return (float) ($invoice->total_amount ?? $invoice->amount ?? 0);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected static function sumLineItems(array $items): float
    {
        return (float) collect($items)->sum(function ($item) {
            $amount = (float) ($item['amount'] ?? 0);
            $discount = isset($item['discount_amount']) && $item['discount_amount'] !== ''
                ? (float) $item['discount_amount']
                : null;

            return $discount ?? $amount;
        });
    }
}
