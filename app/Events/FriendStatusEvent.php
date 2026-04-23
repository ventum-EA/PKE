<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FriendStatusEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public string $status, // 'online', 'offline', 'in_game'
        public ?string $name = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PresenceChannel('online-users')];
    }

    public function broadcastAs(): string
    {
        return 'status.changed';
    }
}
