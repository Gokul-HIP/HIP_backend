<?php

namespace App\Events\Pharmacy;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PharmacyOrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $orderNumber,
        public string $status,
        public string $paymentStatus,
        public int $pharmacyId,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('pharmacy.'.$this->pharmacyId),
            new PrivateChannel('pharmacy-order.'.$this->orderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PharmacyOrderUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'order_number' => $this->orderNumber,
            'status' => $this->status,
            'payment_status' => $this->paymentStatus,
        ];
    }
}
