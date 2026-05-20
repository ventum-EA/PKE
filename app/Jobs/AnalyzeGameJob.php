<?php

namespace App\Jobs;

use App\Models\Game;
use App\Repositories\GameMoveRepository;
use App\Repositories\GameRepository;
use App\Services\StockfishService;
use App\Support\ChessAnalyzer;
use App\Support\ExplanationGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Deep server-side Stockfish analysis.
 * Dispatched when the user requests server-side analysis (higher depth).
 * Browser WASM handles quick analysis; this handles thorough analysis.
 */
class AnalyzeGameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 600;  // 10 min max

    public function __construct(
        public int $gameId,
        public int $depth = 18,
    ) {}

    public function handle(
        StockfishService $stockfish,
        GameRepository $gameRepo,
        GameMoveRepository $moveRepo,
    ): void {
        $game = $gameRepo->findById($this->gameId);

        if (!$stockfish->isAvailable()) {
            Log::warning("Stockfish binary not available, skipping analysis for game {$this->gameId}");
            return;
        }

        // Read moves the client already populated with correct fen_before/fen_after
        $existingMoves = $moveRepo->getByGameId($this->gameId);
        if ($existingMoves->isEmpty() || !$existingMoves->first()->getAttribute('fen_after')) {
            Log::warning("No client-analyzed moves found for game {$this->gameId}; client-side analysis must run first.");
            return;
        }

        // Collect distinct FENs in playing order (starting + after each move)
        $fens = [$existingMoves->first()->getAttribute('fen_before')];
        foreach ($existingMoves as $m) {
            $fens[] = $m->getAttribute('fen_after');
        }

        $evals = $stockfish->analyzePositions($fens, $this->depth);

        // Rebuild move records with deeper eval data
        $now = now();
        $rebuiltMoves = [];
        foreach ($existingMoves as $i => $m) {
            $evalBefore = $evals[$i]['eval'] ?? 0;
            $evalAfter = $evals[$i + 1]['eval'] ?? 0;
            $bestMove = $evals[$i]['bestMove'] ?? null;
            $evalDiff = abs($evalAfter - $evalBefore);
            $color = $m->getAttribute('color');
            $classification = ChessAnalyzer::classify($evalBefore, $evalAfter, $color);
            $errorCategory = ChessAnalyzer::isError($classification)
                ? ChessAnalyzer::categorize($i, $existingMoves->count(), $m->getAttribute('move_san'), $m->getAttribute('fen_before'))
                : null;
            $explanation = ExplanationGenerator::generate($classification, $errorCategory, $m->getAttribute('move_san'), $bestMove);

            $rebuiltMoves[] = [
                'game_id' => $this->gameId,
                'move_number' => $m->getAttribute('move_number'),
                'color' => $color,
                'move_san' => $m->getAttribute('move_san'),
                'move_uci' => $m->getAttribute('move_uci'),
                'fen_before' => $m->getAttribute('fen_before'),
                'fen_after' => $m->getAttribute('fen_after'),
                'eval_before' => round($evalBefore, 2),
                'eval_after' => round($evalAfter, 2),
                'eval_diff' => round($evalDiff, 2),
                'best_move' => $bestMove,
                'classification' => $classification->value,
                'error_category' => $errorCategory?->value,
                'explanation' => $explanation,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $moveRepo->deleteByGameId($this->gameId);
        if (!empty($rebuiltMoves)) {
            $moveRepo->bulkInsert($rebuiltMoves);
        }
        $gameRepo->update($game, ['is_analyzed' => true]);

        Log::info("Game {$this->gameId} deep-analyzed: " . count($rebuiltMoves) . " moves at depth {$this->depth}");
    }

}
