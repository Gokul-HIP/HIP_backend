<?php

namespace App\Services\Api;

use App\Models\Invoice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentApiService
{
    /**
    * Create an invoice for a cashier payment flow.
    *
    * Expected $payload keys:
    * - primary_person_id (int|null)
    * - person_id (int, required)
    * - service_types (array)
    * - invoice_details (array)
    * - subtotal (float)
    * - total_gst (float)
    * - service_charges (float)
    * - payment_gateway_charges (float)
    * - total_amount (float)
    * - discount_price (float)
    * - prescription_file (UploadedFile|null)
    *
    * Returns:
    * - ['invoice' => Invoice, 'coins_earned' => int]
    */
    public function createInvoiceForPayment(array $payload): array
    {
        $primaryPersonId = $payload['primary_person_id'] ?? null;
        $personId = (int) ($payload['person_id'] ?? 0);
        $serviceTypes = $payload['service_types'] ?? [];
        $invoiceDetails = $payload['invoice_details'] ?? [];

        $subtotal = (float) ($payload['subtotal'] ?? 0);
        $totalGst = (float) ($payload['total_gst'] ?? 0);
        $serviceCharges = (float) ($payload['service_charges'] ?? 0);
        $paymentGatewayCharges = (float) ($payload['payment_gateway_charges'] ?? 0);
        $totalAmount = (float) ($payload['total_amount'] ?? 0);
        $discountPrice = (float) ($payload['discount_price'] ?? 0);

        /** @var UploadedFile|null $prescriptionFile */
        $prescriptionFile = $payload['prescription_file'] ?? null;

        $prescriptionPath = null;
        if ($prescriptionFile instanceof UploadedFile) {
            $ext = $prescriptionFile->getClientOriginalExtension();
            $fileName = Str::uuid() . '.' . ($ext ?: 'pdf');
            $prescriptionFile->storeAs('invoices/prescriptions', $fileName, 'public');
            $prescriptionPath = 'invoices/prescriptions/' . $fileName;
        }

        $coinsEarned = (int) round($totalAmount * 0.01);

        try {
            $invoice = Invoice::create([
                'primary_person_id' => $primaryPersonId,
                'person_id' => $personId,
                'service_types' => $serviceTypes,
                'invoice_details' => $invoiceDetails,
                'prescription_img' => $prescriptionPath,
                'total_amount' => $totalAmount,
                'total_gst' => $totalGst,
                'amount' => $subtotal,
                'service_charges' => $serviceCharges,
                'payment_gateway_charges' => $paymentGatewayCharges,
                'discount_price' => $discountPrice > 0 ? $discountPrice : null,
                'status' => 'pending',
                'payment_method' => null,
                'coins_earned' => $coinsEarned,
            ]);
        } catch (\Exception $e) {
            if ($prescriptionPath && Storage::disk('public')->exists($prescriptionPath)) {
                Storage::disk('public')->delete($prescriptionPath);
            }
            throw $e;
        }

        return [
            'invoice' => $invoice,
            'coins_earned' => $coinsEarned,
        ];
    }
}

