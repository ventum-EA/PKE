<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateMultiplayerGameRequest;
use App\Models\OnlineGame;
use App\Services\MultiplayerService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MultiplayerController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected MultiplayerService $service,
    ) {}

    /* ─── Game creation ────────────────────────────────────── */

    /**
     * POST /api/multiplayer/create — create an invite game.
     */
    public function create(CreateMultiplayerGameRequest $request): JsonResponse
    {
        $game = $this->service->createInviteGame(
            $request->user()->id,
            $request->input('color', 'random'),
            $request->integer('time_control', 600),
            $request->boolean('rated', true),
        );

        return $this->success('Game created.', [
            'game_id'      => $game->id,
            'invite_token' => $game->invite_token,
            'invite_url'   => "/multiplayer/join/{$game->invite_token}",
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /api/multiplayer/join/{token} — join via invite link.
     */
    public function join(string $token, Request $request): JsonResponse
    {
        try {
            $game = $this->service->joinByInvite($token, $request->user()->id);
            return $this->success('Joined game.', [
                'game' => $this->service->getGameState($game),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }

    /* ─── Matchmaking ──────────────────────────────────────── */

    /**
     * POST /api/multiplayer/queue/join — enter the matchmaking queue.
     */
    public function joinQueue(Request $request): JsonResponse
    {
        $request->validate([
            'time_control' => 'nullable|integer|in:180,300,600,900,1800',
        ]);

        $user = $request->user();
        $this->service->joinQueue(
            $user->id,
            (int) $user->elo_rating,
            $request->integer('time_control', 600),
        );

        return $this->success('Joined queue.', [
            'in_queue' => true,
            'queue_count' => $this->service->queueCount($request->integer('time_control', 600)),
        ]);
    }

    /**
     * POST /api/multiplayer/queue/leave — leave the queue.
     */
    public function leaveQueue(Request $request): JsonResponse
    {
        $this->service->leaveQueue($request->user()->id);
        return $this->success('Left queue.', ['in_queue' => false]);
    }

    /**
     * GET /api/multiplayer/queue/poll — poll for a match.
     */
    public function pollQueue(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        if (!$this->service->isInQueue($userId)) {
            // Check if we were matched already (game created by opponent's poll)
            $activeGame = OnlineGame::where('status', 'active')
                ->where(fn($q) => $q->where('white_id', $userId)->orWhere('black_id', $userId))
                ->orderByDesc('created_at')
                ->first();

            if ($activeGame && $activeGame->created_at->diffInSeconds(now()) < 30) {
                return $this->success('Match found!', [
                    'matched' => true,
                    'game'    => $this->service->getGameState($activeGame),
                ]);
            }

            return $this->success('Not in queue.', ['matched' => false, 'in_queue' => false]);
        }

        $game = $this->service->findMatch($userId);

        if ($game) {
            return $this->success('Match found!', [
                'matched' => true,
                'game'    => $this->service->getGameState($game),
            ]);
        }

        return $this->success('Searching...', [
            'matched'     => false,
            'in_queue'    => true,
            'queue_count' => $this->service->queueCount($request->integer('time_control', 600)),
        ]);
    }

    /* ─── Game state ───────────────────────────────────────── */

    /**
     * GET /api/multiplayer/{id} — poll game state.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $game = OnlineGame::findOrFail($id);
        $userId = $request->user()->id;

        // Only players can see the game (or anyone if spectating is enabled later)
        if ($game->white_id !== $userId && $game->black_id !== $userId) {
            return $this->error('Not a player in this game.', Response::HTTP_FORBIDDEN);
        }

        return $this->success('Game state.', [
            'game' => $this->service->getGameState($game),
        ]);
    }

    /**
     * POST /api/multiplayer/{id}/move — make a move.
     */
    public function move(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'san'            => 'required|string|max:10',
            'uci'            => 'required|string|max:6',
            'fen'            => 'required|string|max:100',
            'is_game_over'   => 'nullable|boolean',
            'is_checkmate'   => 'nullable|boolean',
            'is_stalemate'   => 'nullable|boolean',
            'is_draw'        => 'nullable|boolean',
            'opening_name'   => 'nullable|string|max:100',
            'opening_eco'    => 'nullable|string|max:5',
            'time_remaining' => 'nullable|integer',
        ]);

        $game = OnlineGame::findOrFail($id);

        try {
            $updated = $this->service->makeMove(
                $game,
                $request->user()->id,
                $request->input('san'),
                $request->input('uci'),
                $request->input('fen'),
                $request->only(['is_game_over', 'is_checkmate', 'is_stalemate', 'is_draw', 'opening_name', 'opening_eco', 'time_remaining']),
            );

            return $this->success('Move recorded.', [
                'game' => $this->service->getGameState($updated),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * POST /api/multiplayer/{id}/resign
     */
    public function resign(int $id, Request $request): JsonResponse
    {
        $game = OnlineGame::findOrFail($id);
        try {
            $updated = $this->service->resign($game, $request->user()->id);
            return $this->success('Resigned.', ['game' => $this->service->getGameState($updated)]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * POST /api/multiplayer/{id}/draw — offer or accept a draw.
     */
    public function draw(int $id, Request $request): JsonResponse
    {
        $request->validate(['action' => 'required|in:offer,accept,decline']);
        $game = OnlineGame::findOrFail($id);
        $userId = $request->user()->id;

        try {
            $updated = match ($request->input('action')) {
                'offer'   => $this->service->offerDraw($game, $userId),
                'accept'  => $this->service->acceptDraw($game, $userId),
                'decline' => $this->service->declineDraw($game, $userId),
            };
            return $this->success('Draw action processed.', ['game' => $this->service->getGameState($updated)]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * POST /api/multiplayer/{id}/timeout — claim win by timeout/abandon.
     */
    public function timeout(int $id, Request $request): JsonResponse
    {
        $game = OnlineGame::findOrFail($id);
        try {
            $updated = $this->service->claimTimeout($game, $request->user()->id);
            return $this->success('Timeout claimed.', ['game' => $this->service->getGameState($updated)]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /* ─── History ──────────────────────────────────────────── */

    /**
     * GET /api/multiplayer/history — user's online game history.
     */
    public function history(Request $request): JsonResponse
    {
        $games = $this->service->getUserGames($request->user()->id);
        return $this->success('Game history.', ['games' => $games]);
    }
}
