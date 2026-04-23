<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameEndEvent implements ShouldBroadcastNow
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
        return 'game.ended';
    }

    public function broadcastWith(): array
    {
        return ['game' => $this->gameState];
    }
}
