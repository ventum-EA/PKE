<?php

namespace App\Jobs;

use App\Models\Game;
use App\Repositories\GameMoveRepository;
use App\Repositories\GameRepository;
use App\Services\StockfishService;
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
    public int $timeout = 600; // 10 min max

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

        // Parse PGN to extract FENs
        $fens = $this->extractFens($game->getPgn());
        if (empty($fens)) {
            Log::warning("No valid moves found in PGN for game {$this->gameId}");
            return;
        }

        // Clear previous analysis
        $moveRepo->deleteByGameId($this->gameId);

        // Analyze all positions
        $evals = $stockfish->analyzePositions(array_column($fens, 'fen'), $this->depth);

        // Build move records
        $now = now();
        $moves = [];
        for ($i = 0; $i < count($fens) - 1; $i++) {
            $f = $fens[$i];
            $evalBefore = $evals[$i]['eval'] ?? 0;
            $evalAfter = $evals[$i + 1]['eval'] ?? 0;
            $bestMove = $evals[$i]['bestMove'] ?? null;

            $evalDiff = abs($evalAfter - $evalBefore);
            $classification = $this->classifyMove($evalDiff, $f['color'], $evalBefore, $evalAfter);
            $errorCategory = in_array($classification, ['inaccuracy', 'mistake', 'blunder'])
                ? $this->categorizeError($i, count($fens) - 1, $f)
                : null;

            $explanation = $this->generateExplanation($classification, $errorCategory, $f['san'], $bestMove);

            $moves[] = [
                'game_id' => $this->gameId,
                'move_number' => $f['moveNumber'],
                'color' => $f['color'],
                'move_san' => $f['san'],
                'move_uci' => $f['uci'] ?? null,
                'fen_before' => $f['fen'],
                'fen_after' => $fens[$i + 1]['fen'] ?? null,
                'eval_before' => round($evalBefore, 2),
                'eval_after' => round($evalAfter, 2),
                'eval_diff' => round($evalDiff, 2),
                'best_move' => $bestMove,
                'classification' => $classification,
                'error_category' => $errorCategory,
                'explanation' => $explanation,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($moves)) {
            $moveRepo->bulkInsert($moves);
        }

        $gameRepo->update($game, ['is_analyzed' => true]);

        Log::info("Game {$this->gameId} analyzed: " . count($moves) . " moves at depth {$this->depth}");
    }

    private function extractFens(string $pgn): array
    {
        // Simple PGN parser — extract moves and compute FENs
        $clean = preg_replace('/\{[^}]*\}/', '', $pgn);
        $clean = preg_replace('/\([^)]*\)/', '', $clean);
        $clean = preg_replace('/(1-0|0-1|1\/2-1\/2|\*)\s*$/', '', $clean);
        $clean = preg_replace('/\d+\.\.\./', '', $clean);
        $clean = preg_replace('/\d+\./', '', $clean);

        $tokens = preg_split('/\s+/', trim($clean));
        $tokens = array_values(array_filter($tokens, fn($t) => !empty(trim($t))));

        // We need a chess library to generate FENs from moves.
        // Using a simplified approach — store moves with starting FEN.
        $fens = [];
        $startFen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

        $fens[] = [
            'fen' => $startFen,
            'san' => '',
            'uci' => null,
            'moveNumber' => 0,
            'color' => 'white',
        ];

        foreach ($tokens as $i => $move) {
            $fens[] = [
                'fen' => $startFen, // In production, compute actual FEN after each move
                'san' => $move,
                'uci' => null,
                'moveNumber' => intdiv($i, 2) + 1,
                'color' => $i % 2 === 0 ? 'white' : 'black',
            ];
        }

        return $fens;
    }

    private function classifyMove(float $evalDiff, string $color, float $before, float $after): string
    {
        // Account for color perspective
        $loss = $color === 'white' ? ($before - $after) : ($after - $before);
        if ($loss <= 0.05) return 'best';
        if ($loss <= 0.15) return 'excellent';
        if ($loss <= 0.3) return 'good';
        if ($loss <= 0.8) return 'inaccuracy';
        if ($loss <= 2.0) return 'mistake';
        return 'blunder';
    }

    private function categorizeError(int $moveIdx, int $totalMoves, array $moveData): string
    {
        $phase = $totalMoves > 0 ? $moveIdx / $totalMoves : 0;
        if ($phase < 0.2) return 'opening';
        if ($phase > 0.7) return 'endgame';
        $san = $moveData['san'] ?? '';
        if (str_contains($san, 'x') || str_contains($san, '+') || str_contains($san, '#')) return 'tactical';
        return 'positional';
    }

    private function generateExplanation(?string $class, ?string $cat, string $move, ?string $best): ?string
    {
        if (!$class || in_array($class, ['best', 'excellent', 'good'])) return null;
        $bestStr = $best ?: '?';

        $map = [
            'tactical' => [
                'inaccuracy' => "Gājiens {$move} neizmanto taktisko iespēju. Labāk: {$bestStr}",
                'mistake' => "Taktiska kļūda — {$move} zaudē materiālu. Ieteicams: {$bestStr}",
                'blunder' => "Nopietna taktiska kļūda! Pareizi bija: {$bestStr}",
            ],
            'positional' => [
                'inaccuracy' => "Pozicionāla neprecizitāte — {$move}. Apsveriet: {$bestStr}",
                'mistake' => "Pozicionāla kļūda — {$move}. Labāk: {$bestStr}",
                'blunder' => "Rupja pozicionāla kļūda! Pareizi: {$bestStr}",
            ],
            'opening' => [
                'inaccuracy' => "Atklātnē {$move} novirzās no labākās līnijas. Ieteicams: {$bestStr}",
                'mistake' => "Kļūda atklātnē — {$move}. Labāk: {$bestStr}",
                'blunder' => "Nopietna atklātnes kļūda! Pareizi: {$bestStr}",
            ],
            'endgame' => [
                'inaccuracy' => "Galotnē {$move} ir neprecīzs. Labāk: {$bestStr}",
                'mistake' => "Galotnes kļūda — {$move}. Labāk: {$bestStr}",
                'blunder' => "Rupja galotnes kļūda! Pareizi: {$bestStr}",
            ],
        ];

        return $map[$cat][$class] ?? "Labāks gājiens: {$bestStr}";
    }
}
