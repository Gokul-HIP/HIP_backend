<?php

namespace App\Services\Api;

use App\Models\HIPUser;
use App\Models\Invoice;
use App\Models\Persons;
use App\Models\UserDevice;
use App\Services\NotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
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
    * - device_id (string)
    * Returns:
    * - ['invoice' => Invoice, 'coins_earned' => int]
    */

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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
        $deviceId = $payload['device_id'] ?? null;

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

            $person = Persons::find($primaryPersonId);

            $primaryPerson = HIPUser::find($person->hip_user_id);

            if ($primaryPerson) {
                $title = 'New Invoice Created';
                $body = 'Your payment of ₹' . number_format($totalAmount, 2) . ' has been created.';
                $data = [
                    'type'       => 'invoice_created',
                    'invoice_id' => (string) $invoice->id,
                    'amount'     => (string) $totalAmount,
                ];

                // Log::info('PaymentApiService: preparing to send invoice notification', [
                //     'user_id'        => $primaryPerson->id,
                //     'invoice_id'     => $invoice->id,
                //     'total_amount'   => $totalAmount,
                //     'device_id_passed' => $deviceId,
                // ]);

                // If a specific device_id was provided, target only that device
                if ($deviceId) {
                    $result = $this->notificationService->sendToDevice(
                        $primaryPerson->id,
                        $deviceId,
                        $title,
                        $body,
                        $data
                    );

                    if ($result) {
                        $invoice->is_notified = true;
                        $invoice->save();
                    }

                    Log::info('PaymentApiService: notification sent to single device', [
                        'user_id'   => $primaryPerson->id,
                        'device_id' => $deviceId,
                        'success'   => $result,
                    ]);
                } else {
                    // Otherwise, send to all registered devices for this user
                    $userDevices = UserDevice::where('user_id', $primaryPerson->id)->get();

                    if ($userDevices->isEmpty()) {
                        Log::warning('PaymentApiService: no user devices found for invoice notification', [
                            'user_id'    => $primaryPerson->id,
                            'invoice_id' => $invoice->id,
                        ]);
                    }

                    foreach ($userDevices as $device) {
                        $result = $this->notificationService->sendToDevice(
                            $device->user_id,
                            $device->device_id,
                            $title,
                            $body,
                            $data
                        );

                        if ($result) {
                            $invoice->is_notified = true;
                            $invoice->save();
                        }

                        Log::info('PaymentApiService: notification attempt for device', [
                            'user_id'   => $device->user_id,
                            'device_id' => $device->device_id,
                            'success'   => $result,
                        ]);
                    }
                }
            } else {
                Log::warning('PaymentApiService: primary person not found for invoice notification', [
                    'primary_person_id' => $primaryPersonId,
                ]);
            }

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

