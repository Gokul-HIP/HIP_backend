<?php

namespace App\Http\Resources\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use App\Models\HIPUser;
use App\Models\RazorpayPayment;
use App\Services\Pharmacy\PharmacyBillCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class PharmacyOrderResource extends JsonResource
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
        $authors = HIPUser::query()
            ->whereIn('id', $this->comments->pluck('author_id')->filter()->unique()->all())
            ->get()
            ->keyBy('id');

        return [
            'id' => (int) $this->id,
            'order_number' => $this->order_number,
            'status' => $status?->value ?? (string) $this->status,
            'status_label' => $status?->label() ?? (string) $this->status,
            'payment_status' => $payment?->value ?? (string) $this->payment_status,
            'person_id' => $this->person_id,
            'patient_user_id' => $this->person?->hip_user_id,
            'person_name' => $personName !== '' ? $personName : null,
            'hospital_id' => (int) $this->hospital_id,
            'hospital_name' => $this->hospital?->name,
            'pharmacy_id' => (int) $this->pharmacy_id,
            'pharmacy_name' => $this->pharmacy?->name,
            'patient' => $this->patientPayload(),
            'pharmacy' => [
                'id' => (int) $this->pharmacy_id,
                'name' => $this->pharmacy?->name,
            ],
            'delivery_name' => $this->delivery_name,
            'delivery_phone' => $this->delivery_phone,
            'delivery_address' => $this->delivery_address,
            'items' => PharmacyOrderItemResource::collection($this->items),
            'subtotal' => (float) $this->subtotal,
            'discount' => (float) $this->discount,
            'delivery_fee' => (float) $this->delivery_fee,
            'tax' => (float) $this->tax,
            'total' => (float) $this->total,
            'invoice_id' => $this->invoice_id ? (int) $this->invoice_id : null,
            'payment' => $this->paymentPayload(),
            'delivery' => $this->deliveryPayload(),
            'comments' => $this->comments->map(function ($comment) use ($authors, $request) {
                $author = $comment->author_id ? $authors->get($comment->author_id) : null;
                $name = trim((string) (($author->first_name ?? '').' '.($author->last_name ?? '')));

                return [
                    'id' => (int) $comment->id,
                    'author_type' => $comment->author_type,
                    'author_name' => $name !== '' ? $name : null,
                    'is_mine' => (string) ($comment->author_id ?? '') !== ''
                        && (string) $comment->author_id === (string) ($request->user()?->id ?? ''),
                    'body' => $comment->body,
                    'created_at' => $comment->created_at?->toIso8601String(),
                    'read' => $comment->read_at !== null,
                ];
            })->values(),
            'steps' => $this->progressSteps(),
            // 'timeline' => $this->statusLogs->map(function ($log) {
            //     $to = PharmacyOrderStatus::tryFrom((string) $log->to_status);

            //     return [
            //         'from_status' => $log->from_status,
            //         'to_status' => $log->to_status,
            //         'label' => $to?->label() ?? (string) $log->to_status,
            //         'note' => $log->note,
            //         'at' => $log->created_at?->toIso8601String(),
            //     ];
            // })->values(),
            'prescription' => $this->prescriptionPayload(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: mixed, name: ?string, hip_id: mixed}
     */
    private function patientPayload(): array
    {
        $name = trim((string) (($this->person->first_name ?? '').' '.($this->person->last_name ?? '')));

        return [
            'id' => $this->person_id,
            'name' => $name !== '' ? $name : null,
            'hip_id' => $this->person?->hipUser?->hip_id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deliveryPayload(): array
    {
        $address = filled($this->delivery_address) ? $this->delivery_address : null;

        return [
            'name' => $this->delivery_name,
            'phone' => $this->delivery_phone,
            'address' => $address,
            'address_missing' => $address === null,
            'partner' => $this->delivery_partner,
            'rider_name' => $this->rider_name,
            'tracking_ref' => $this->tracking_ref,
            'expected_at' => $this->expected_delivery_at?->toIso8601String(),
        ];
    }

    /**
     * Fixed prescription-details timeline. Cancelled and rejected orders return one terminal step.
     *
     * @return array<int, array{key: string, label: string, state: string, at: ?string}>
     */
    private function progressSteps(): array
    {
        $status = $this->status instanceof PharmacyOrderStatus
            ? $this->status
            : PharmacyOrderStatus::tryFrom((string) $this->status);

        if ($status === PharmacyOrderStatus::Cancelled || $status === PharmacyOrderStatus::Rejected) {
            return [[
                'key' => $status->value,
                'label' => $status->label(),
                'state' => 'done',
                'at' => $this->stepAt($status->value),
            ]];
        }

        $definitions = [
            ['key' => 'verified', 'label' => 'Verified', 'status' => PharmacyOrderStatus::InReview->value],
            ['key' => 'packed', 'label' => 'Packed', 'status' => PharmacyOrderStatus::ReadyForDelivery->value],
            ['key' => 'dispatched', 'label' => 'Dispatched', 'status' => PharmacyOrderStatus::Dispatched->value],
            ['key' => 'out_for_delivery', 'label' => 'Out for delivery', 'status' => PharmacyOrderStatus::OutForDelivery->value],
            ['key' => 'delivered', 'label' => 'Delivered', 'status' => PharmacyOrderStatus::Delivered->value],
        ];
        $current = $status?->value;
        $activeIndex = match ($current) {
            PharmacyOrderStatus::InReview->value => 0,
            PharmacyOrderStatus::ReadyForDelivery->value => 1,
            PharmacyOrderStatus::Dispatched->value => 2,
            PharmacyOrderStatus::OutForDelivery->value => 3,
            default => null,
        };
        $completedThrough = match ($current) {
            PharmacyOrderStatus::PendingPayment->value, PharmacyOrderStatus::Processing->value => 0,
            PharmacyOrderStatus::ReadyForDelivery->value => 0,
            PharmacyOrderStatus::Dispatched->value => 1,
            PharmacyOrderStatus::OutForDelivery->value => 2,
            PharmacyOrderStatus::Delivered->value => 4,
            default => -1,
        };

        $steps = [];
        foreach ($definitions as $index => $definition) {
            $state = 'pending';
            if ($current === PharmacyOrderStatus::Delivered->value || $index <= $completedThrough) {
                $state = 'done';
            } elseif ($activeIndex === $index) {
                $state = 'active';
            }

            $steps[] = [
                'key' => $definition['key'],
                'label' => $definition['label'],
                'state' => $state,
                'at' => $this->stepAt($definition['status']),
            ];
        }

        return $steps;
    }

    private function stepAt(string $toStatus): ?string
    {
        $log = $this->statusLogs->first(function ($log) use ($toStatus) {
            return (string) $log->to_status === $toStatus;
        });

        if ($log?->created_at) {
            return $log->created_at->toIso8601String();
        }

        $column = match ($toStatus) {
            PharmacyOrderStatus::InReview->value => $this->in_review_at,
            PharmacyOrderStatus::ReadyForDelivery->value => $this->ready_at,
            PharmacyOrderStatus::Dispatched->value => $this->dispatched_at,
            PharmacyOrderStatus::OutForDelivery->value => $this->out_for_delivery_at,
            PharmacyOrderStatus::Delivered->value => $this->delivered_at,
            PharmacyOrderStatus::Cancelled->value => $this->cancelled_at,
            default => null,
        };

        return $column?->toIso8601String();
    }

    private function paymentStatusLabel(
        ?PharmacyOrderStatus $status,
        ?PharmacyPaymentStatus $payment,
        ?string $invoiceStatus
    ): string {
        return match ($payment) {
            PharmacyPaymentStatus::Paid => 'paid',
            PharmacyPaymentStatus::Failed => 'failed',
            PharmacyPaymentStatus::Refunded => 'refunded',
            default => ($status === PharmacyOrderStatus::PendingPayment || $invoiceStatus === 'pending')
                ? 'pending'
                : 'unpaid',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(): array
    {
        $status = $this->status instanceof PharmacyOrderStatus
            ? $this->status
            : PharmacyOrderStatus::tryFrom((string) $this->status);
        $payment = $this->payment_status instanceof PharmacyPaymentStatus
            ? $this->payment_status
            : PharmacyPaymentStatus::tryFrom((string) $this->payment_status);
        $invoice = $this->invoice;
        $transaction = $invoice?->transactions()->latest('id')->first();
        $razorpay = $invoice
            ? RazorpayPayment::query()->where('invoice_id', $invoice->id)->latest('id')->first()
            : null;
        $invoiceStatus = $invoice ? (string) $invoice->status : null;
        $amountPaid = 0.0;
        if ($transaction && (string) $transaction->status === 'completed') {
            $amountPaid = round((float) $transaction->total_amount, 2);
        } elseif ($razorpay && in_array((string) $razorpay->payment_status, ['captured', 'paid'], true)) {
            $amountPaid = round((float) $razorpay->amount_paid, 2);
        }

        return [
            'invoice_id' => $invoice ? (int) $invoice->id : null,
            'invoice_status' => $invoiceStatus,
            'amount_due' => $invoice ? round((float) $invoice->total_amount, 2) : null,
            'amount_paid' => $amountPaid,
            'coins_applied' => (int) ($invoice->coins_applied ?? 0),
            'coins_discount' => round((float) ($transaction->discount_amount ?? 0), 2),
            // 'method' => $invoice?->payment_method ?: $razorpay?->payment_method,
            'method' => $this->paymentMethod($razorpay, $invoice),
            'paid_on' => $this->paid_at?->toIso8601String(),
            'transaction_ref' => $transaction
                ? 'TXN-'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT)
                : null,
            'status_label' => $this->paymentStatusLabel($status, $payment, $invoiceStatus),
            'attempted_at' => $this->paymentAttemptedAt($razorpay, $transaction),
            'breakdown' => $this->paymentBreakdown($invoice),
            'can_pay' => $status === PharmacyOrderStatus::PendingPayment && $invoiceStatus === 'pending',
            'retry' => $payment === PharmacyPaymentStatus::Failed,
            'invoice_pdf_url' => $invoice && $invoiceStatus === 'completed'
                ? URL::temporarySignedRoute(
                    'hospital.pay-bill-invoice-download',
                    now()->addDay(),
                    ['invoice_id' => $invoice->id]
                )
                : null,
        ];
    }

    private function paymentMethod(mixed $razorpay, mixed $invoice): ?string
    {
        $razorpayMethod = trim((string) ($razorpay->payment_method ?? ''));
        if ($razorpayMethod !== '') {
            return $razorpayMethod;
        }

        $invoiceMethod = trim((string) ($invoice->payment_method ?? ''));
        if ($invoiceMethod === '' || $invoiceMethod === 'razorpay') {
            return null;
        }

        return $invoiceMethod;
    }

    private function paymentAttemptedAt(mixed $razorpay, mixed $transaction): ?string
    {
        if ($razorpay) {
            $at = $razorpay->created_at ?? $razorpay->updated_at;

            return $at?->toIso8601String();
        }

        return $transaction?->updated_at?->toIso8601String();
    }

    /**
     * Invoice columns once a bill exists. Before that, PharmacyBillCalculator matches the invoice that will be created.
     *
     * @return array{subtotal: float, discount: float, delivery_fee: float, gst: float, service_charges: float, gateway_charges: float, total: float}
     */
    private function paymentBreakdown(mixed $invoice): array
    {
        if ($invoice) {
            return [
                'subtotal' => round((float) $this->subtotal, 2),
                'discount' => round((float) $this->discount, 2),
                'delivery_fee' => round((float) $this->delivery_fee, 2),
                'gst' => round((float) ($invoice->total_gst ?? 0), 2),
                'service_charges' => round((float) ($invoice->service_charges ?? 0), 2),
                'gateway_charges' => round((float) ($invoice->payment_gateway_charges ?? 0), 2),
                'total' => round((float) ($invoice->total_amount ?? 0), 2),
            ];
        }

        $bill = app(PharmacyBillCalculator::class)->calculate(
            (float) $this->subtotal,
            (float) $this->discount,
            (float) $this->delivery_fee
        );

        return [
            'subtotal' => $bill['subtotal'],
            'discount' => $bill['discount'],
            'delivery_fee' => $bill['delivery_fee'],
            'gst' => $bill['tax'],
            'service_charges' => $bill['service_charge'],
            'gateway_charges' => $bill['gateway_charge'],
            'total' => $bill['total'],
        ];
    }

    /**
     * @return array{file_name: ?string, mime: ?string, download_url: string}|null
     */
    private function prescriptionPayload(): ?array
    {
        $document = $this->prescriptionDocument;

        if (! $document) {
            return null;
        }

        $mime = null;
        if ($document->document_path && Storage::disk('local')->exists($document->document_path)) {
            $mime = Storage::disk('local')->mimeType($document->document_path) ?: null;
        }

        return [
            'file_name' => $document->document_name,
            'mime' => $mime,
            'download_url' => URL::temporarySignedRoute(
                'pharmacy.orders.prescription',
                now()->addMinutes(10),
                ['order' => $this->id]
            ),
        ];
    }
}
