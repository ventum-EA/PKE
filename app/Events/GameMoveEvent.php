<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameMoveEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $gameId,
        public array $gameState,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'move.made';
    }

    public function broadcastWith(): array
    {
        return ['game' => $this->gameState];
    }
}
