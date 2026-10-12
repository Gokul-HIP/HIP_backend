<?php

namespace App\Services\Pharmacy;

use App\Enums\PharmacyOrderStatus;
use App\Enums\PharmacyPaymentStatus;
use App\Models\Persons;
use App\Models\PharmacyOrder;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class PharmacyNotificationService
{
    public function __construct(private NotificationService $notifications) {}

    public function paymentRequested(PharmacyOrder $order): void
    {
        $this->send(
            $order,
            'payment_requested',
            'Payment request for your prescription order',
            'Order '.$order->order_number.' is ready to pay. Total '.$this->amount($order).'.'
        );
    }

    public function paymentReceived(PharmacyOrder $order): void
    {
        $this->send(
            $order,
            'payment_received',
            'Payment received',
            'Payment received for order '.$order->order_number.'. '.$this->amount($order).'.'
        );
    }

    public function paymentFailed(PharmacyOrder $order): void
    {
        $this->send(
            $order,
            'payment_failed',
            'Payment failed. Tap to retry',
            'Payment failed for order '.$order->order_number.'. '.$this->amount($order).'. Tap to retry.'
        );
    }

    public function statusChanged(PharmacyOrder $order, PharmacyOrderStatus $status): void
    {
        [$key, $title] = match ($status) {
            PharmacyOrderStatus::PendingPayment => ['payment_requested', 'Payment request for your prescription order'],
            PharmacyOrderStatus::ReadyForDelivery => ['ready_for_delivery', 'Your prescription order is ready for delivery'],
            PharmacyOrderStatus::Dispatched => ['dispatched', 'Your prescription order has been dispatched'],
            PharmacyOrderStatus::OutForDelivery => ['out_for_delivery', 'Your prescription order is out for delivery'],
            PharmacyOrderStatus::Delivered => ['delivered', 'Your prescription order was delivered'],
            PharmacyOrderStatus::Cancelled => ['cancelled', 'Your prescription order was cancelled'],
            PharmacyOrderStatus::Rejected => ['rejected', 'Your prescription order was rejected'],
            default => [null, null],
        };

        if ($key === null || $title === null) {
            return;
        }

        $body = $status === PharmacyOrderStatus::PendingPayment
            ? 'Order '.$order->order_number.' is ready to pay. Total '.$this->amount($order).'.'
            : 'Order '.$order->order_number.' — '.$title.'. Total '.$this->amount($order).'.';

        $this->send($order, $key, $title, $body);
    }

    public function pharmacistComment(PharmacyOrder $order): void
    {
        $this->send(
            $order,
            'pharmacist_comment',
            'New message from the pharmacy',
            'New message about order '.$order->order_number.'.'
        );
    }

    private function send(PharmacyOrder $order, string $eventKey, string $title, string $body): void
    {
        try {
            $userId = $this->recipientUserId($order);
            if ($userId === '') {
                Log::warning('Pharmacy push skipped; family primary user was not found', [
                    'order_id' => $order->id,
                    'notification_type' => $eventKey,
                ]);

                return;
            }

            $data = $this->payload($order, $eventKey);
            $this->notifications->notifyUser($userId, $title, $body, $data);

            Log::info('Pharmacy push stored', [
                'order_id' => $order->id,
                'user_id' => $userId,
                'title' => $title,
                'data' => $data,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Pharmacy push failed', [
                'order_id' => $order->id,
                'notification_type' => $eventKey,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Same family-primary resolution as PaymentApiService::createInvoiceForPayment.
     */
    private function recipientUserId(PharmacyOrder $order): string
    {
        $person = Persons::query()->find((string) $order->person_id);
        if (! $person) {
            return '';
        }

        $requestedPrimaryId = (string) ($person->parent_id ?: $person->id);
        $requestedPrimary = Persons::query()->find($requestedPrimaryId) ?: $person;
        $primaryPerson = ! empty($requestedPrimary->parent_id)
            ? (Persons::query()->find((string) $requestedPrimary->parent_id) ?: $requestedPrimary)
            : $requestedPrimary;

        return trim((string) ($primaryPerson->hip_user_id ?? ''));
    }

    /**
     * @return array<string, string>
     */
    private function payload(PharmacyOrder $order, string $eventKey): array
    {
        $deeplink = config('hip.pharmacy_deeplink', []);
        $screen = (string) ($deeplink['screen'] ?? 'prescription_details');
        $route = rtrim((string) ($deeplink['route'] ?? '/prescription-details'), '/');
        // $param = (string) ($deeplink['order_param'] ?? 'order_id');
        $id = (string) $order->id;
        $path = $route.'/'.$id;

        return [
            'type' => 'pharmacy_order',
            'notification_type' => $eventKey,
            'source' => 'pharmacy',
            'screen' => $screen,
            // 'route' => $route,
            // 'url' => $route.'?'.$param.'='.$id,
            'route' => $path,
            'url' => $path,
            'pharmacy_order_id' => $id,
            'order_number' => (string) $order->order_number,
            'status' => $this->statusValue($order),
            'payment_status' => $this->paymentValue($order),
        ];
    }

    private function amount(PharmacyOrder $order): string
    {
        return '₹'.number_format((float) $order->total, 2);
    }

    private function statusValue(PharmacyOrder $order): string
    {
        return $order->status instanceof PharmacyOrderStatus
            ? $order->status->value
            : (string) $order->status;
    }

    private function paymentValue(PharmacyOrder $order): string
    {
        return $order->payment_status instanceof PharmacyPaymentStatus
            ? $order->payment_status->value
            : (string) $order->payment_status;
    }
}
