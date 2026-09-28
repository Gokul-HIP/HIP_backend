<?php

namespace App\Services;

use App\Models\Invoice;
use App\Modules\Automation\Support\InvoiceAutomationScope;
use Illuminate\Support\Facades\Schema;

class InvoiceDocumentService
{
    /**
     * @var list<string>
     */
    public const PLACEHOLDERS = [
        'hospital_id',
        'hospital_name',
        'hospital_address',
        'hospital_contact',
        'invoice_title',
        'invoice_id',
        'invoice_number',
        'invoice_date',
        'patient_name',
        'patient_id',
        'patient_contact',
        'service_type',
        'service_reference',
        'service_description',
        'original_amount',
        'discount_amount',
        'discounted_amount',
        'service_charges',
        'payment_gateway_charges',
        'gst_amount',
        'invoice_total',
        'payment_status',
        'payment_method',
        'transaction_id',
    ];

    /**
     * @return array<string, string>
     */
    public function placeholders(Invoice $invoice): array
    {
        InvoiceAutomationScope::hydrate($invoice);

        $hospital = InvoiceAutomationScope::hospital($invoice);
        $hospitalId = InvoiceAutomationScope::hospitalId($invoice);
        $patient = $invoice->person ?? $invoice->primaryPerson;
        $patientName = trim(($patient?->first_name ?? '').' '.($patient?->last_name ?? ''));
        $original = (float) ($invoice->amount ?? 0);
        $discount = (float) ($invoice->discount_price ?? 0);
        $discounted = round(max(0, $original - $discount), 2);
        $serviceTypes = is_array($invoice->service_types) ? $invoice->service_types : [];
        $transaction = null;
        if (Schema::hasTable('transactions')) {
            $transaction = $invoice->relationLoaded('transactions')
                ? $invoice->transactions->sortByDesc('id')->first()
                : $invoice->transactions()->latest('id')->first();
        }

        $reference = $invoice->doctor_booking_id
            ? 'Booking #'.$invoice->doctor_booking_id
            : ($invoice->second_opinion_id
                ? 'Second opinion #'.$invoice->second_opinion_id
                : ($invoice->diagnostic_test_booking_id
                    ? 'Diagnostic #'.$invoice->diagnostic_test_booking_id
                    : ''));

        $values = [
            'hospital_id' => $hospitalId !== null ? (string) $hospitalId : '',
            'hospital_name' => (string) ($hospital?->name ?? ''),
            'hospital_address' => trim((string) ($hospital?->address ?? $hospital?->admin_address ?? '')),
            'hospital_contact' => (string) ($hospital?->admin_contact ?? $hospital?->admin_emergency_contact ?? ''),
            'invoice_title' => 'Tax Invoice',
            'invoice_id' => (string) $invoice->id,
            'invoice_number' => (string) $invoice->id,
            'invoice_date' => optional($invoice->updated_at ?? $invoice->created_at)?->format('d M Y') ?? '',
            'patient_name' => $patientName !== '' ? $patientName : 'Patient',
            'patient_id' => (string) ($invoice->person_id ?? ''),
            'patient_contact' => (string) ($patient?->mobile ?? $patient?->email ?? ''),
            'service_type' => implode(', ', $serviceTypes),
            'service_reference' => $reference,
            'service_description' => $this->serviceDescription($invoice),
            'original_amount' => number_format($original, 2, '.', ''),
            'discount_amount' => number_format($discount, 2, '.', ''),
            'discounted_amount' => number_format($discounted, 2, '.', ''),
            'service_charges' => number_format((float) ($invoice->service_charges ?? 0), 2, '.', ''),
            'payment_gateway_charges' => number_format((float) ($invoice->payment_gateway_charges ?? 0), 2, '.', ''),
            'gst_amount' => number_format((float) ($invoice->total_gst ?? 0), 2, '.', ''),
            'invoice_total' => number_format((float) ($invoice->total_amount ?? 0), 2, '.', ''),
            'payment_status' => (string) $invoice->status,
            'payment_method' => (string) ($invoice->payment_method ?? ''),
            'transaction_id' => $transaction
                ? 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT)
                : '',
        ];

        return $values;
    }

    public function layout(): string
    {
        $stored = function_exists('app_setting') ? app_setting('invoice_layout') : null;
        if (! is_string($stored) || trim($stored) === '') {
            $stored = config('settings.invoice.layout');
        }
        $layout = is_string($stored) && trim($stored) !== ''
            ? $stored
            : (string) config('invoice.layout', '');

        return $layout;
    }

    public function renderHtml(Invoice $invoice): string
    {
        $html = $this->layout();
        $vars = $this->placeholders($invoice);

        $rendered = preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function (array $matches) use ($vars) {
            $key = $matches[1];
            if (! in_array($key, self::PLACEHOLDERS, true)) {
                return '';
            }

            return e($vars[$key] ?? '');
        }, $html) ?? $html;

        return preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $rendered) ?? $rendered;
    }

    protected function serviceDescription(Invoice $invoice): string
    {
        $details = $invoice->invoice_details;
        if (! is_array($details) || $details === []) {
            return '';
        }

        if (isset($details[0]) && is_array($details[0])) {
            return (string) ($details[0]['service'] ?? $details[0]['doctor_name'] ?? '');
        }

        return '';
    }
}
