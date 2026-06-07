<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Opening;
use App\Repositories\GameMoveRepository;
use App\Repositories\GameRepository;
use App\Repositories\TrainingSessionRepository;
use Illuminate\Contracts\Auth\Guard;

class TrainingService
{
    public function __construct(
        protected TrainingSessionRepository $sessionRepo,
        protected GameMoveRepository $moveRepo,
        protected GameRepository $gameRepo,
        protected Guard $auth
    ) {}

    public function generatePuzzleFromErrors(int $gameId): array
    {
        $errors = $this->moveRepo->getErrorsByGameId($gameId);

        if ($errors->isEmpty()) {
            return ['puzzles' => [], 'message' => 'Nav kļūdu, no kurām ģenerēt uzdevumus.'];
        }

        $puzzles = [];
        foreach ($errors->take(5) as $error) {
            $fenBefore = $error->getFenBefore();
            $bestMove = $error->getBestMove();

            // Skip if we don't have a valid FEN or best move
            if (!$fenBefore || $fenBefore === 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1') {
                // Only skip if this is clearly the starting position AND move_number > 1
                if ($error->getMoveNumber() > 1) continue;
            }
            if (!$bestMove) continue;

            // Validate best move — old broken analyses saved garbage like "1" from multipv parsing bug
            if (strlen($bestMove) < 2 || !preg_match('/^[a-hKQRBNOo]/', $bestMove)) continue;

            // Normalize the best move: store BOTH SAN and UCI forms
            // so we can match either format from the frontend
            $bestMoveSan = $error->getBestMove();
            $bestMoveUci = $this->sanToApproxUci($bestMoveSan);

            $session = $this->sessionRepo->store([
                'user_id' => $this->auth->id(),
                'game_id' => $gameId,
                'fen' => $fenBefore,
                'correct_move' => $bestMoveUci ?: $bestMoveSan, // Store UCI for comparison
                'category' => $error->getErrorCategory() ?? 'tactical',
                'hint' => $error->getExplanation(),
            ]);

            $puzzles[] = [
                'id' => $session->getId(),
                'fen' => $session->getFen(),
                'category' => $session->getCategory(),
                'hint' => $session->getHint(),
                'move_number' => $error->getMoveNumber(),
                'correct_san' => $bestMoveSan,
            ];
        }

        return ['puzzles' => $puzzles];
    }

    public function submitAnswer(int $sessionId, string $userMove): array
    {
        $session = $this->sessionRepo->findById($sessionId);
        $correctMove = strtolower(trim($session->getCorrectMove()));
        $userMoveClean = strtolower(trim($userMove));

        // Match either UCI (e2e4) or the stored form
        // Also handle UCI with/without promotion suffix
        $isCorrect = $userMoveClean === $correctMove
            || substr($userMoveClean, 0, 4) === substr($correctMove, 0, 4);

        $this->sessionRepo->update($session, [
            'user_move' => $userMove,
            'is_correct' => $isCorrect,
        ]);

        return [
            'is_correct' => $isCorrect,
            'correct_move' => $session->getCorrectMove(),
            'hint' => $session->getHint(),
        ];
    }

    /**
     * Approximate SAN to UCI conversion.
     * This is a best-effort conversion since we don't have the board state.
     * The frontend sends UCI (e2e4), so we try to store UCI.
     */
    private function sanToApproxUci(?string $san): ?string
    {
        if (!$san) return null;
        // If it already looks like UCI (4-5 chars, all lowercase letters/digits), keep it
        if (preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $san)) {
            return $san;
        }
        // Otherwise return null — comparison will use the SAN form
        return null;
    }

    public function getProgress(): array
    {
        return $this->sessionRepo->getUserProgress($this->auth->id());
    }

    /**
     * Generate a detailed training progress report comparing error rates
     * in games played BEFORE and AFTER training sessions.
     *
     * Fulfils spec §2.2.19 (Treniņu progresijas atskaite).
     */
    public function progressReport(): array
    {
        $userId = $this->auth->id();

        // Find the date of the user's first completed training session
        $firstTraining = $this->sessionRepo->getFirstCompletedDate($userId);

        if (!$firstTraining) {
            return [
                'has_data' => false,
                'message' => 'Vēl nav pabeigtu treniņu sesiju. Pabeidz vismaz vienu treniņu, lai redzētu progresa atskaiti.',
            ];
        }

        // Get error rates in games BEFORE first training
        $beforeErrors = $this->gameRepo->getErrorRatesByPeriod(
            $userId,
            null,
            $firstTraining
        );

        // Get error rates in games AFTER first training
        $afterErrors = $this->gameRepo->getErrorRatesByPeriod(
            $userId,
            $firstTraining,
            null
        );

        // Calculate improvement per category
        $categories = ['tactical', 'positional', 'opening', 'endgame'];
        $comparison = [];

        foreach ($categories as $cat) {
            $before = $beforeErrors[$cat] ?? 0;
            $after  = $afterErrors[$cat] ?? 0;

            $change = $before > 0
                ? round((($before - $after) / $before) * 100, 1)
                : 0;

            $comparison[] = [
                'category'    => $cat,
                'before'      => round($before, 2),
                'after'       => round($after, 2),
                'change_pct'  => $change,
                'improved'    => $change > 0,
            ];
        }

        // Training session stats
        $sessionStats = $this->sessionRepo->getUserProgress($userId);

        // Weekly trend: error rate per game over the last 90 days
        $weeklyTrend = $this->gameRepo->getWeeklyErrorTrend($userId, 90);

        // Achievement milestones
        $totalSessions = collect($sessionStats['by_category'] ?? [])
            ->sum(fn ($c) => (int) ($c->total ?? $c['total'] ?? 0));
        $totalCorrect = collect($sessionStats['by_category'] ?? [])
            ->sum(fn ($c) => (int) ($c->correct ?? $c['correct'] ?? 0));
        $accuracy = $totalSessions > 0
            ? round(($totalCorrect / $totalSessions) * 100, 1)
            : 0;

        return [
            'has_data'       => true,
            'comparison'     => $comparison,
            'session_stats'  => $sessionStats,
            'weekly_trend'   => $weeklyTrend,
            'totals'         => [
                'sessions'  => $totalSessions,
                'correct'   => $totalCorrect,
                'accuracy'  => $accuracy,
            ],
            'training_start' => $firstTraining,
        ];
    }

    /**
     * Generate a personalized opening training session based on the user's
     * weakest openings (lowest win rate, with at least 2 games played).
     *
     * Returns the 3 weakest openings joined to their canonical Opening records
     * so the frontend can use chess.js to play through the move sequence and
     * generate "what comes next?" practice puzzles.
     */
    public function generateOpeningTraining(?int $minGames = 2, int $limit = 3): array
    {
        $userId = $this->auth->id();
        $stats  = $this->gameRepo->getOpeningStats($userId);

        $weak = collect($stats)
            ->map(function ($row) {
                $total = (int) ($row->total ?? 0);
                $wins  = (int) ($row->wins ?? 0);
                return [
                    'opening_name' => $row->opening_name ?? null,
                    'opening_eco'  => $row->opening_eco ?? null,
                    'total'        => $total,
                    'wins'         => $wins,
                    'losses'       => (int) ($row->losses ?? 0),
                    'draws'        => (int) ($row->draws ?? 0),
                    'win_rate'     => $total > 0 ? round(($wins / $total) * 100, 1) : 0.0,
                ];
            })
            ->filter(fn ($o) => $o['total'] >= $minGames && !empty($o['opening_name']))
            ->sortBy([
                ['win_rate', 'asc'],
                ['total', 'desc'],
            ])
            ->take($limit)
            ->values();

        if ($weak->isEmpty()) {
            return [
                'weak_openings' => [],
                'message'       => 'Vēl nav pietiekami daudz partiju, lai noteiktu vājākās atklātnes. Spēlē vismaz 2 partijas vienā atklātnē.',
            ];
        }

        // Join with canonical Opening records (try name → name_lv → eco)
        $enriched = $weak->map(function (array $stat) {
            $opening = Opening::where('name', $stat['opening_name'])
                ->orWhere('name_lv', $stat['opening_name'])
                ->first();

            if (!$opening && !empty($stat['opening_eco'])) {
                $opening = Opening::where('eco', $stat['opening_eco'])->first();
            }

            return array_merge($stat, [
                'name_lv'    => $opening?->name_lv ?? $stat['opening_name'],
                'moves'      => $opening?->moves,
                'summary_lv' => $opening?->summary_lv,
                'ideas_lv'   => $opening?->ideas_lv ?? [],
                'eco'        => $opening?->eco ?? $stat['opening_eco'],
                'category'   => $opening?->category,
            ]);
        })->filter(fn ($o) => !empty($o['moves']))->values();

        return [
            'weak_openings'    => $enriched->all(),
            'unmatched_count'  => $weak->count() - $enriched->count(),
        ];
    }
}
