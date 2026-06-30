<?php

namespace App\Events;

use App\Models\HIPUser;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast to the mobile app when email verification completes on any device.
 *
 * Uses ShouldBroadcastNow so Reverb receives the event immediately (no queue worker
 * required). Implements the ShouldBroadcast contract family used by Laravel Reverb.
 */
class EmailVerified implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HIPUser $user
    ) {}

    /**
     * Private channel scoped to the verified user only.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
        ];
    }

    /**
     * Client event name (mobile listens for ".EmailVerified").
     */
    public function broadcastAs(): string
    {
        return 'EmailVerified';
    }

    /**
     * @return array{verified: bool, user_id: string}
     */
    public function broadcastWith(): array
    {
        return [
            'verified' => true,
            'user_id'  => (string) $this->user->id,
        ];
    }
}
