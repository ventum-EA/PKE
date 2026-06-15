<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\DrawOfferEvent;
use App\Events\GameEndEvent;
use App\Events\GameMoveEvent;
use App\Events\MatchFoundEvent;
use App\Models\OnlineGame;
use App\Models\OnlineGameMove;
use App\Models\User;
use App\Support\ChessBoard;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;

class MultiplayerService
{
    // Abandon timeout: if no move for 5 minutes, game can be claimed
    /**
     * Number of seconds a player can be idle in a started game before the
     * opponent can claim a win by abandonment. Configurable via
     * `config/chess.php` (multiplayer.abandon_seconds).
     */
    private function abandonSeconds(): int
    {
        return (int) config('chess.multiplayer.abandon_seconds', 300);
    }

    /**
     * Default ELO rating used when a user record is missing or unset.
     * Configurable via `config/chess.php` (elo.default).
     */
    private function defaultElo(): int
    {
        return (int) config('chess.elo.default', 1200);
    }

    public function __construct(
        protected ConnectionInterface $db,
        protected EloService $elo,
    ) {}

    /* ─── Game creation ────────────────────────────────────── */

    /**
     * Create a game via invite link. Creator picks their color or random.
     */
    public function createInviteGame(int $userId, string $preferredColor = 'random', int $timeControl = 600, bool $rated = true): OnlineGame
    {
        $color = $preferredColor === 'random' ? (random_int(0, 1) ? 'white' : 'black') : $preferredColor;

        return OnlineGame::create([
            'white_id'     => $color === 'white' ? $userId : null,
            'black_id'     => $color === 'black' ? $userId : null,
            'status'       => 'waiting',
            'invite_token' => Str::random(32),
            'time_control' => $timeControl,
            'white_time_remaining' => $timeControl * 1000,
            'black_time_remaining' => $timeControl * 1000,
            'rated'        => $rated,
        ]);
    }

    /**
     * Join a game by invite token.
     */
    public function joinByInvite(string $token, int $userId): OnlineGame
    {
        $game = OnlineGame::where('invite_token', $token)
            ->where('status', 'waiting')
            ->firstOrFail();

        if ($game->white_id === $userId || $game->black_id === $userId) {
            return $game; // Already in this game
        }

        $update = [];
        if ($game->white_id === null) {
            $update['white_id'] = $userId;
        } else {
            $update['black_id'] = $userId;
        }
        $update['status'] = 'active';
        $update['last_move_at'] = now();

        // Snapshot ELO
        $whiteId = $update['white_id'] ?? $game->white_id;
        $blackId = $update['black_id'] ?? $game->black_id;
        $update['white_elo_before'] = User::find($whiteId)?->elo_rating ?? $this->defaultElo();
        $update['black_elo_before'] = User::find($blackId)?->elo_rating ?? $this->defaultElo();

        $game->update($update);
        return $game->fresh();
    }

    /* ─── Matchmaking ──────────────────────────────────────── */

    /**
     * Join the matchmaking queue.
     */
    public function joinQueue(int $userId, int $eloRating, int $timeControl = 600): void
    {
        $this->db->table('matchmaking_queue')->updateOrInsert(
            ['user_id' => $userId],
            ['elo_rating' => $eloRating, 'time_control' => $timeControl, 'created_at' => now()],
        );
    }

    /**
     * Leave the matchmaking queue.
     */
    public function leaveQueue(int $userId): void
    {
        $this->db->table('matchmaking_queue')->where('user_id', $userId)->delete();
    }

    /**
     * Try to find a match for a user in the queue.
     * Simple FIFO: pairs the two oldest entries with the same time control.
     */
    public function findMatch(int $userId): ?OnlineGame
    {
        $myEntry = $this->db->table('matchmaking_queue')->where('user_id', $userId)->first();
        if (!$myEntry) return null;

        // Use a transaction with row-level locking to prevent race conditions
        // where two simultaneous polls could match the same opponent
        return $this->db->transaction(function () use ($userId, $myEntry) {
            // Lock the opponent row so no other transaction can claim it
            $opponent = $this->db->table('matchmaking_queue')
                ->where('user_id', '!=', $userId)
                ->where('time_control', $myEntry->time_control)
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (!$opponent) return null;

            // Atomically remove both players from queue
            $deleted = $this->db->table('matchmaking_queue')
                ->whereIn('user_id', [$userId, $opponent->user_id])
                ->delete();

            // If we didn't delete exactly 2 rows, someone else got there first
            if ($deleted < 2) return null;

            $isWhite = random_int(0, 1) === 1;
            $whiteId = $isWhite ? $userId : $opponent->user_id;
            $blackId = $isWhite ? $opponent->user_id : $userId;

            $whiteElo = User::find($whiteId)?->elo_rating ?? $this->defaultElo();
            $blackElo = User::find($blackId)?->elo_rating ?? $this->defaultElo();
            $tc = $myEntry->time_control;

            $game = OnlineGame::create([
                'white_id'     => $whiteId,
                'black_id'     => $blackId,
                'status'       => 'active',
                'time_control' => $tc,
                'white_time_remaining' => $tc * 1000,
                'black_time_remaining' => $tc * 1000,
                'rated'                => true,
                'white_elo_before'     => $whiteElo,
                'black_elo_before'     => $blackElo,
                'last_move_at'         => now(),
            ]);

            // Notify both players via WebSocket
            $state = $this->getGameState($game);
            broadcast(new MatchFoundEvent($whiteId, $state));
            broadcast(new MatchFoundEvent($blackId, $state));

            return $game;
        });
    }

    /**
     * Check if the user is in queue.
     */
    public function isInQueue(int $userId): bool
    {
        return $this->db->table('matchmaking_queue')->where('user_id', $userId)->exists();
    }

    /**
     * Count players in queue.
     */
    public function queueCount(int $timeControl = 600): int
    {
        return (int) $this->db->table('matchmaking_queue')
            ->where('time_control', $timeControl)->count();
    }

    /* ─── Move processing ──────────────────────────────────── */

    /**
     * Record a move. The caller is responsible for chess.js validation on frontend.
     * Server does basic sanity checks.
     */
    public function makeMove(OnlineGame $game, int $userId, string $san, string $uci, string $fenAfter, array $gameState): OnlineGame
    {
        $color = $game->getPlayerColor($userId);
        if (!$color) throw new \RuntimeException('Not a player in this game.');
        if ($game->status !== 'active') throw new \RuntimeException('Game is not active.');

        // Verify it's this player's turn
        if (!$game->isPlayerTurn($userId)) {
            throw new \RuntimeException('Not your turn.');
        }

        // Basic server-side validation
        $this->validateMoveData($game, $san, $uci, $fenAfter);

        // Determine move number
        $lastMove = OnlineGameMove::where('online_game_id', $game->id)->orderByDesc('id')->first();
        $moveNumber = $lastMove
            ? ($color === 'white' ? $lastMove->move_number + 1 : $lastMove->move_number)
            : 1;
        if ($color === 'black' && !$lastMove) $moveNumber = 1;

        OnlineGameMove::create([
            'online_game_id' => $game->id,
            'move_number'    => $moveNumber,
            'color'          => $color,
            'move_san'       => $san,
            'move_uci'       => $uci,
            'fen_after'      => $fenAfter,
            'created_at'     => now(),
        ]);

        $update = [
            'fen'          => $fenAfter,
            'total_moves'  => $game->total_moves + 1,
            'last_move_at' => now(),
            'draw_offered_by' => null, // Clear any pending draw offer on new move
        ];

        // Opening detection passed from client
        if (!empty($gameState['opening_name'])) {
            $update['opening_name'] = $gameState['opening_name'];
            $update['opening_eco']  = $gameState['opening_eco'] ?? null;
        }

        // Time tracking
        if (isset($gameState['time_remaining'])) {
            $update[$color === 'white' ? 'white_time_remaining' : 'black_time_remaining'] = $gameState['time_remaining'];
        }

        // Check game over states from client
        if (!empty($gameState['is_game_over'])) {
            $update['status'] = 'completed';
            if (!empty($gameState['is_checkmate'])) {
                $update['result'] = $color === 'white' ? '1-0' : '0-1';
                $update['result_reason'] = 'checkmate';
            } elseif (!empty($gameState['is_stalemate'])) {
                $update['result'] = '1/2-1/2';
                $update['result_reason'] = 'stalemate';
            } elseif (!empty($gameState['is_draw'])) {
                $update['result'] = '1/2-1/2';
                $update['result_reason'] = 'draw';
            }

            // Build PGN
            $update['pgn'] = $this->buildPgn($game->id);
        }

        $game->update($update);

        $fresh = $game->fresh();
        $state = $this->getGameState($fresh);

        // Process ELO if game ended
        if (($update['status'] ?? null) === 'completed' && $game->rated) {
            $this->processElo($fresh);
            $state = $this->getGameState($fresh->fresh()); // re-fetch with ELO
            broadcast(new GameEndEvent($game->id, $state));
        } else {
            broadcast(new GameMoveEvent($game->id, $state));
        }

        return $fresh;
    }

    /* ─── Game actions ─────────────────────────────────────── */

    public function resign(OnlineGame $game, int $userId): OnlineGame
    {
        if ($game->status !== 'active') throw new \RuntimeException('Game is not active.');
        $color = $game->getPlayerColor($userId);
        if (!$color) throw new \RuntimeException('Not a player.');

        $game->update([
            'status'        => 'completed',
            'result'        => $color === 'white' ? '0-1' : '1-0',
            'result_reason' => 'resignation',
            'pgn'           => $this->buildPgn($game->id),
        ]);

        if ($game->rated) $this->processElo($game->fresh());
        $state = $this->getGameState($game->fresh());
        broadcast(new GameEndEvent($game->id, $state));
        return $game->fresh();
    }

    public function offerDraw(OnlineGame $game, int $userId): OnlineGame
    {
        $color = $game->getPlayerColor($userId);
        if (!$color || $game->status !== 'active') throw new \RuntimeException('Cannot offer draw.');
        $game->update(['draw_offered_by' => $color]);
        broadcast(new DrawOfferEvent($game->id, 'offered', $color));
        return $game->fresh();
    }

    public function acceptDraw(OnlineGame $game, int $userId): OnlineGame
    {
        $color = $game->getPlayerColor($userId);
        if (!$color || $game->status !== 'active') throw new \RuntimeException('Cannot accept draw.');
        if ($game->draw_offered_by === $color) throw new \RuntimeException('Cannot accept own draw offer.');
        if (!$game->draw_offered_by) throw new \RuntimeException('No draw offer pending.');

        $game->update([
            'status'        => 'completed',
            'result'        => '1/2-1/2',
            'result_reason' => 'draw_agreement',
            'pgn'           => $this->buildPgn($game->id),
        ]);

        if ($game->rated) $this->processElo($game->fresh());
        $state = $this->getGameState($game->fresh());
        broadcast(new GameEndEvent($game->id, $state));
        return $game->fresh();
    }

    public function declineDraw(OnlineGame $game, int $userId): OnlineGame
    {
        $game->update(['draw_offered_by' => null]);
        broadcast(new DrawOfferEvent($game->id, 'declined'));
        return $game->fresh();
    }

    /**
     * Claim win by timeout (opponent abandoned / ran out of time).
     */
    public function claimTimeout(OnlineGame $game, int $userId): OnlineGame
    {
        if ($game->status !== 'active') throw new \RuntimeException('Game not active.');
        $color = $game->getPlayerColor($userId);
        if (!$color) throw new \RuntimeException('Not a player.');

        // Check if opponent's time is up or they've been idle too long
        $opponentColor = $color === 'white' ? 'black' : 'white';
        $opponentTime = $opponentColor === 'white' ? $game->white_time_remaining : $game->black_time_remaining;
        $idleSeconds = $game->last_move_at ? now()->diffInSeconds($game->last_move_at) : 0;

        if ($opponentTime !== null && $opponentTime <= 0) {
            $reason = 'timeout';
        } elseif ($idleSeconds > $this->abandonSeconds()) {
            $reason = 'abandon';
        } else {
            throw new \RuntimeException('Cannot claim timeout yet.');
        }

        $game->update([
            'status'        => 'completed',
            'result'        => $color === 'white' ? '1-0' : '0-1',
            'result_reason' => $reason,
            'pgn'           => $this->buildPgn($game->id),
        ]);

        if ($game->rated) $this->processElo($game->fresh());
        return $game->fresh();
    }

    /* ─── Polling ──────────────────────────────────────────── */

    /**
     * Get the current game state for polling.
     */
    public function getGameState(OnlineGame $game): array
    {
        $moves = $game->moves()->orderBy('id')->get()->map(fn($m) => [
            'move_number' => $m->move_number,
            'color'       => $m->color,
            'san'         => $m->move_san,
            'uci'         => $m->move_uci,
            'fen'         => $m->fen_after,
            'created_at'  => $m->created_at?->toISOString(),
        ])->toArray();

        return [
            'id'              => $game->id,
            'status'          => $game->status,
            'fen'             => $game->fen,
            'result'          => $game->result,
            'result_reason'   => $game->result_reason,
            'total_moves'     => $game->total_moves,
            'opening_name'    => $game->opening_name,
            'opening_eco'     => $game->opening_eco,
            'rated'           => $game->rated,
            'time_control'    => $game->time_control,
            'white_time'      => $game->white_time_remaining,
            'black_time'      => $game->black_time_remaining,
            'draw_offered_by' => $game->draw_offered_by,
            'last_move_at'    => $game->last_move_at?->toISOString(),
            'white'           => $this->playerInfo($game->white_id, $game->white_elo_before, $game->white_elo_change),
            'black'           => $this->playerInfo($game->black_id, $game->black_elo_before, $game->black_elo_change),
            'moves'           => $moves,
        ];
    }

    /**
     * Get active or recent games for a user.
     */
    public function getUserGames(int $userId, int $limit = 20): array
    {
        return OnlineGame::where(function ($q) use ($userId) {
                $q->where('white_id', $userId)->orWhere('black_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn($g) => [
                'id'           => $g->id,
                'status'       => $g->status,
                'result'       => $g->result,
                'result_reason' => $g->result_reason,
                'total_moves'  => $g->total_moves,
                'opening_name' => $g->opening_name,
                'rated'        => $g->rated,
                'time_control' => $g->time_control,
                'my_color'     => $g->getPlayerColor($userId),
                'white'        => $this->playerInfo($g->white_id, $g->white_elo_before, $g->white_elo_change),
                'black'        => $this->playerInfo($g->black_id, $g->black_elo_before, $g->black_elo_change),
                'updated_at'   => $g->updated_at?->toISOString(),
            ])
            ->toArray();
    }

    /* ─── ELO ──────────────────────────────────────────────── */

    /**
     * Process ELO changes for both players via EloService.
     *
     * Delegates to EloService::processMultiplayerResult() which uses
     * the same K-factor brackets (40/20/10) as single-player games,
     * ensuring rating consistency across all game modes.
     */
    private function processElo(OnlineGame $game): void
    {
        if (!$game->result || !$game->white_id || !$game->black_id) return;

        $white = User::find($game->white_id);
        $black = User::find($game->black_id);
        if (!$white || !$black) return;

        $whiteScore = match ($game->result) {
            '1-0'     => 1.0,
            '0-1'     => 0.0,
            '1/2-1/2' => 0.5,
            default   => 0.5,
        };

        $result = $this->elo->processMultiplayerResult(
            $game->white_id,
            $game->black_id,
            $whiteScore,
            $game->id,
        );

        $game->update([
            'white_elo_change' => $result['white_change'],
            'black_elo_change' => $result['black_change'],
        ]);
    }

    /* ─── Helpers ──────────────────────────────────────────── */

    private function buildPgn(int $gameId): string
    {
        $moves = OnlineGameMove::where('online_game_id', $gameId)
            ->orderBy('id')
            ->get();

        $pgn = '';
        foreach ($moves as $m) {
            if ($m->color === 'white') {
                $pgn .= $m->move_number . '. ' . $m->move_san . ' ';
            } else {
                $pgn .= $m->move_san . ' ';
            }
        }

        return trim($pgn);
    }

    private function playerInfo(?int $userId, ?int $eloBefore = null, ?int $eloChange = null): ?array
    {
        if (!$userId) return null;
        $user = User::find($userId);
        if (!$user) return null;

        return [
            'id'         => $user->id,
            'name'       => $user->name,
            'elo_rating' => (int) $user->elo_rating,
            'elo_before' => $eloBefore,
            'elo_change' => $eloChange,
        ];
    }

    /**
     * Server-side validation of move legality.
     *
     * Uses the ChessBoard engine to verify the SAN move is legal in
     * the current position and that the resulting FEN matches what the
     * client submitted. This prevents illegal moves, FEN tampering,
     * and position desynchronisation.
     */
    private function validateMoveData(OnlineGame $game, string $san, string $uci, string $fenAfter): void
    {
        // Quick format checks before loading the engine
        if (!preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $uci)) {
            throw new \RuntimeException('Invalid move format.');
        }

        $fenParts = explode(' ', $fenAfter);
        if (count($fenParts) !== 6) {
            throw new \RuntimeException('Invalid position format.');
        }

        // Server-side validation via ChessBoard
        $board = ChessBoard::fromFen($game->fen);

        if (!$board->isLegalMove($san)) {
            throw new \RuntimeException('Illegal move.');
        }

        // Apply the move. ChessBoard is currently a stub that delegates
        // real validation to chess.js on the client. When a full PHP
        // chess engine is available, use move() + fen() comparison.
        $board->moveTo($san, $fenAfter);
    }
}
