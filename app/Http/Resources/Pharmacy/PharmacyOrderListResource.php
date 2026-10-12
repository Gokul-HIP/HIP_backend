<?php

namespace App\Http\Resources\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class PharmacyOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof PharmacyOrderStatus
            ? $this->status
            : PharmacyOrderStatus::tryFrom((string) $this->status);
        $payment = $this->payment_status instanceof PharmacyPaymentStatus
            ? $this->payment_status
            : PharmacyPaymentStatus::tryFrom((string) $this->payment_status);
        $personName = trim((string) (($this->person->first_name ?? '').' '.($this->person->last_name ?? '')));

        return [
            'id' => (int) $this->id,
            'order_number' => $this->order_number,
            'status' => $status?->value ?? (string) $this->status,
            'status_label' => $status?->label() ?? (string) $this->status,
            'payment_status' => $payment?->value ?? (string) $this->payment_status,
            'person_name' => $personName !== '' ? $personName : null,
            'patient' => [
                'id' => $this->person_id,
                'name' => $personName !== '' ? $personName : null,
                'hip_id' => $this->person?->hipUser?->hip_id,
            ],
            'pharmacy_name' => $this->pharmacy?->name,
            'pharmacy' => [
                'id' => (int) $this->pharmacy_id,
                'name' => $this->pharmacy?->name,
            ],
            'delivery' => [
                'name' => $this->delivery_name,
                'phone' => $this->delivery_phone,
                'address' => filled($this->delivery_address) ? $this->delivery_address : null,
                'address_missing' => ! filled($this->delivery_address),
                'partner' => $this->delivery_partner,
                'rider_name' => $this->rider_name,
                'tracking_ref' => $this->tracking_ref,
                'expected_at' => $this->expected_delivery_at?->toIso8601String(),
            ],
            'hospital_name' => $this->hospital?->name,
            'total' => (float) $this->total,
            'can_pay' => $this->canPay(),
            'prescription_download_url' => $this->prescription_document_id
                ? URL::temporarySignedRoute(
                    'pharmacy.orders.prescription',
                    now()->addMinutes(10),
                    ['order' => $this->id]
                )
                : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'unread_comments_count' => (int) ($this->unread_comments_count ?? 0),
        ];
    }

    private function canPay(): bool
    {
        $status = $this->status instanceof PharmacyOrderStatus
            ? $this->status
            : PharmacyOrderStatus::tryFrom((string) $this->status);
        $invoiceStatus = $this->invoice ? (string) $this->invoice->status : null;

        return $status === PharmacyOrderStatus::PendingPayment && $invoiceStatus === 'pending';
    }
}
