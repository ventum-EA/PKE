<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OnlineGame;

/**
 * MatchmakingService — focused facade for queue and invite-link operations.
 *
 * Split out from MultiplayerService for clarity: this class only deals with
 * how a player gets into a game (queues, invites, ELO-bracketed matching).
 * Once the game is created, MultiplayerService handles the in-game lifecycle
 * (moves, draws, resigns, timeouts).
 *
 * Both services share the same OnlineGame model and can be safely used
 * together — the split is a separation of concerns, not a hard boundary.
 */
final class MatchmakingService
{
    public function __construct(
        protected MultiplayerService $multiplayer,
    ) {}

    /**
     * Create a private game with an invite token. The creator picks their color
     * (or 'random'), and the link can be shared with one specific opponent.
     */
    public function createInvite(int $userId, string $preferredColor = 'random', int $timeControl = 600, bool $rated = true): OnlineGame
    {
        return $this->multiplayer->createInviteGame($userId, $preferredColor, $timeControl, $rated);
    }

    /**
     * Join a previously-created invite game by its token.
     */
    public function joinByInvite(string $token, int $userId): OnlineGame
    {
        return $this->multiplayer->joinByInvite($token, $userId);
    }

    /**
     * Add the user to the public matchmaking queue.
     */
    public function joinQueue(int $userId, int $eloRating, int $timeControl = 600): void
    {
        $this->multiplayer->joinQueue($userId, $eloRating, $timeControl);
    }

    /**
     * Remove the user from the matchmaking queue (e.g. they navigated away).
     */
    public function leaveQueue(int $userId): void
    {
        $this->multiplayer->leaveQueue($userId);
    }

    /**
     * Try to find an ELO-appropriate opponent in the queue. Returns null
     * if no suitable match exists yet; the caller should poll periodically.
     */
    public function findMatch(int $userId): ?OnlineGame
    {
        return $this->multiplayer->findMatch($userId);
    }

    public function isInQueue(int $userId): bool
    {
        return $this->multiplayer->isInQueue($userId);
    }

    public function queueCount(int $timeControl = 600): int
    {
        return $this->multiplayer->queueCount($timeControl);
    }
}
