<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\GameData;
use App\Models\Game;
use App\Repositories\GameMoveRepository;
use App\Repositories\GameRepository;
use App\Support\ChessAnalyzer;
use Illuminate\Contracts\Auth\Guard;

class GameService
{
    public function __construct(
        protected GameRepository $gameRepo,
        protected GameMoveRepository $moveRepo,
        protected Guard $auth
    ) {}

    public function createGame(GameData $data): array
    {
        $gameArray = $data->toArray();
        $gameArray['user_id'] = $this->auth->id();
        $game = $this->gameRepo->store($gameArray);

        return ['game' => $game];
    }

    public function updateGame(GameData $data): Game
    {
        $game = $this->gameRepo->findById($data->game_id);
        $this->gameRepo->update($game, $data->toArray());
        return $game;
    }

    /**
     * Returns a summary of the stored analysis for a game.
     * Moves are analyzed either by the browser Stockfish WASM (quick)
     * or by AnalyzeGameJob (deep, server-side). This method only reads
     * the already-persisted results — it does not run analysis itself.
     */
    public function analyzeGame(int $gameId, int $depth = 15): array
    {
        $game = $this->gameRepo->findById($gameId);
        $moves = $this->moveRepo->getByGameId($gameId);

        if ($moves->isEmpty()) {
            return [
                'game_id' => $gameId,
                'analyzed' => false,
                'message' => 'Analīze vēl nav veikta. Klients veic analīzi caur Stockfish WASM, '
                    . 'vai pieprasiet servera dziļo analīzi ar parametru ?server=true.',
            ];
        }

        $errorValues = ChessAnalyzer::errorClassificationValues();
        $errors = $moves->whereIn('classification', $errorValues);

        $avgLoss = $moves->avg('eval_diff');

        return [
            'game_id' => $gameId,
            'analyzed' => true,
            'total_moves' => $moves->count(),
            'errors_count' => $errors->count(),
            'blunders' => $errors->where('classification', 'blunder')->count(),
            'mistakes' => $errors->where('classification', 'mistake')->count(),
            'inaccuracies' => $errors->where('classification', 'inaccuracy')->count(),
            'avg_eval_loss' => $avgLoss !== null ? round($avgLoss, 2) : null,
        ];
    }

    public function getDashboardStats(): array
    {
        $userId = $this->auth->id();
        $playerStats = $this->gameRepo->getPlayerStats($userId);
        $openingStats = $this->gameRepo->getOpeningStats($userId);
        $errorStats = $this->gameRepo->getErrorStats($userId);
        $progressTrend = $this->gameRepo->getProgressTrend($userId);

        return [
            'summary' => $playerStats,
            'openings' => $openingStats,
            'errors' => $errorStats,
            'progress_trend' => $progressTrend,
        ];
    }
}
