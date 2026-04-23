<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrawOfferEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $gameId,
        public string $action, // 'offered', 'accepted', 'declined'
        public ?string $offeredBy = null,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("game.{$this->gameId}")];
    }

    public function broadcastAs(): string
    {
        return 'draw.update';
    }
}
