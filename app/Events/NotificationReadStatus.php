<?php

namespace App\Events;

use App\Models\HIPUser;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the mobile app when notification read status changes.
 *
 * Mobile listens on private channel user.{id} for ".NotificationReadStatus".
 */
class NotificationReadStatus implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HIPUser $user,
        public bool $notificationRead
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NotificationReadStatus';
    }

    /**
     * @return array{notification_read: bool, user_id: string}
     */
    public function broadcastWith(): array
    {
        return [
            'notification_read' => $this->notificationRead,
            'user_id'           => (string) $this->user->id,
        ];
    }
}
