<?php

namespace App\Events\Pharmacy;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PharmacyOrderCommentAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $orderId,
        public int $commentId,
        public string $body,
        public string $author,
        public ?string $time,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('pharmacy-order.'.$this->orderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PharmacyOrderCommentAdded';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->orderId,
            'comment_id' => $this->commentId,
            'body' => $this->body,
            'author' => $this->author,
            'time' => $this->time,
        ];
    }
}
