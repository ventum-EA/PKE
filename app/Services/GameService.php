<?php

namespace App\Services;

use App\Data\GameData;
use App\Models\Game;
use App\Repositories\GameRepository;
use App\Repositories\GameMoveRepository;
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
     * Simulates Stockfish analysis by generating move evaluations.
     * In production, this would call the actual Stockfish engine via a job queue.
     */
    public function analyzeGame(int $gameId, int $depth = 15): array
    {
        $game = $this->gameRepo->findById($gameId);

        // Delete previous analysis
        $this->moveRepo->deleteByGameId($gameId);

        // Parse PGN moves (simplified - in production use a chess library)
        $moves = $this->parsePgnMoves($game->getPgn());
        $analyzedMoves = [];
        $prevEval = 0.2; // Starting eval slightly favoring white

        foreach ($moves as $index => $move) {
            $moveNumber = intdiv($index, 2) + 1;
            $color = $index % 2 === 0 ? 'white' : 'black';

            // Simulate engine evaluation (in production: actual Stockfish call)
            $evalShift = $this->simulateEvalShift($move, $color, $prevEval);
            $newEval = $prevEval + $evalShift;
            $evalDiff = abs($evalShift);

            $classification = $this->classifyMove($evalDiff);
            $errorCategory = $classification !== 'best' && $classification !== 'excellent' && $classification !== 'good'
                ? $this->categorizeError($moveNumber, count($moves), $move)
                : null;

            $explanation = $this->generateExplanation($classification, $errorCategory, $move, $moveNumber);

            $moveData = [
                'game_id' => $gameId,
                'move_number' => $moveNumber,
                'color' => $color,
                'move_san' => $move,
                'eval_before' => round($prevEval, 2),
                'eval_after' => round($newEval, 2),
                'eval_diff' => round($evalDiff, 2),
                'classification' => $classification,
                'error_category' => $errorCategory,
                'explanation' => $explanation,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $analyzedMoves[] = $moveData;
            $prevEval = $newEval;
        }

        $this->moveRepo->bulkInsert($analyzedMoves);
        $this->gameRepo->update($game, ['is_analyzed' => true]);

        $errors = collect($analyzedMoves)->whereIn('classification', ['inaccuracy', 'mistake', 'blunder']);

        return [
            'game_id' => $gameId,
            'total_moves' => count($analyzedMoves),
            'errors_count' => $errors->count(),
            'blunders' => $errors->where('classification', 'blunder')->count(),
            'mistakes' => $errors->where('classification', 'mistake')->count(),
            'inaccuracies' => $errors->where('classification', 'inaccuracy')->count(),
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

    private function parsePgnMoves(string $pgn): array
    {
        // Remove comments, variations, result
        $clean = preg_replace('/\{[^}]*\}/', '', $pgn);
        $clean = preg_replace('/\([^)]*\)/', '', $clean);
        $clean = preg_replace('/\d+\.\.\./', '', $clean);
        $clean = preg_replace('/\d+\./', '', $clean);
        $clean = preg_replace('/(1-0|0-1|1\/2-1\/2|\*)/', '', $clean);

        $moves = preg_split('/\s+/', trim($clean));
        return array_values(array_filter($moves, fn($m) => !empty(trim($m))));
    }

    private function simulateEvalShift(string $move, string $color, float $prevEval): float
    {
        $base = (mt_rand(-30, 30) / 100);
        // Captures and checks tend to be more volatile
        if (str_contains($move, 'x')) $base *= 1.5;
        if (str_contains($move, '+') || str_contains($move, '#')) $base *= 2;
        // Add occasional blunders
        if (mt_rand(1, 100) <= 8) $base = (mt_rand(0, 1) ? 1 : -1) * (mt_rand(100, 300) / 100);

        return $color === 'white' ? $base : -$base;
    }

    private function classifyMove(float $evalDiff): string
    {
        if ($evalDiff < 0.1) return 'best';
        if ($evalDiff < 0.25) return 'excellent';
        if ($evalDiff < 0.5) return 'good';
        if ($evalDiff < 1.0) return 'inaccuracy';
        if ($evalDiff < 2.0) return 'mistake';
        return 'blunder';
    }

    private function categorizeError(int $moveNumber, int $totalMoves, string $move): string
    {
        $phase = $moveNumber / max($totalMoves / 2, 1);
        if ($phase < 0.3) return 'opening';
        if ($phase > 0.8) return 'endgame';
        if (str_contains($move, 'x') || str_contains($move, '+')) return 'tactical';
        return 'positional';
    }

    private function generateExplanation(?string $classification, ?string $category, string $move, int $moveNumber): ?string
    {
        if (!$classification || in_array($classification, ['best', 'excellent', 'good'])) {
            return null;
        }

        $explanations = [
            'tactical' => [
                "Gājiens {$move} palaiž garām taktisku iespēju. Pārbaudiet alternatīvās apmaiņas.",
                "Šis gājiens neizmanto taktisko potenciālu pozīcijā.",
                "Taktiska kļūda — pretinieks iegūst materiālu pārsvaru.",
            ],
            'positional' => [
                "Gājiens {$move} novājina pozīciju. Apsveriet centrālu kontroli.",
                "Pozicionāla neprecizitāte — figūras koordinācija pasliktinās.",
                "Šis gājiens paver pretiniekam iniciatīvu.",
            ],
            'opening' => [
                "Atklātnē gājiens {$move} novirzās no labākās teorijas līnijas.",
                "Pārāk agresīvs gājiens atklātnē — attīstiet figūras vispirms.",
                "Šī variante atklātnē zaudē tempu.",
            ],
            'endgame' => [
                "Galotnē gājiens {$move} zaudē izdevīgu pozīciju.",
                "Galotnes tehnika — karalis jāaktivizē agrāk.",
                "Bandinieka virzīšana šeit bija prioritāte.",
            ],
        ];

        $pool = $explanations[$category ?? 'positional'] ?? $explanations['positional'];
        return $pool[array_rand($pool)];
    }
}
