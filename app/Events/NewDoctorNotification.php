<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDoctorNotification implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification,
        public string $doctorId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('doctor.'.$this->doctorId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewDoctorNotification';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'data' => $this->notification->data ?? [],
            'is_read' => (bool) $this->notification->is_read,
            'created_at' => $this->notification->created_at?->toIso8601String(),
        ];
    }
}
