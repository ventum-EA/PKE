<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DailyPuzzle;
use App\Models\DailyPuzzleAttempt;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyPuzzleController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/daily-puzzle — get today's puzzle + user's attempt if any.
     */
    public function today(Request $request): JsonResponse
    {
        $puzzle = DailyPuzzle::where('puzzle_date', Carbon::today()->toDateString())->first();

        if (!$puzzle) {
            return $this->error('No daily puzzle available today.', 404);
        }

        $attempt = null;
        if ($request->user()) {
            $attempt = DailyPuzzleAttempt::where('user_id', $request->user()->id)
                ->where('daily_puzzle_id', $puzzle->id)
                ->first();
        }

        // Leaderboard: top 10 fastest solvers today
        $leaderboard = DailyPuzzleAttempt::where('daily_puzzle_id', $puzzle->id)
            ->where('solved', true)
            ->whereNotNull('solve_time_seconds')
            ->join('users', 'users.id', '=', 'daily_puzzle_attempts.user_id')
            ->select('users.name', 'daily_puzzle_attempts.solve_time_seconds', 'daily_puzzle_attempts.attempts')
            ->orderBy('daily_puzzle_attempts.solve_time_seconds')
            ->limit(10)
            ->get();

        $totalSolvers = DailyPuzzleAttempt::where('daily_puzzle_id', $puzzle->id)
            ->where('solved', true)->count();

        $totalAttempts = DailyPuzzleAttempt::where('daily_puzzle_id', $puzzle->id)->count();

        return $this->success('Daily puzzle loaded.', [
            'puzzle' => [
                'id'             => $puzzle->id,
                'date'           => $puzzle->puzzle_date->toDateString(),
                'fen'            => $puzzle->fen,
                'correct_move'   => $attempt?->solved ? $puzzle->correct_move : null,
                'theme'          => $puzzle->theme,
                'theme_lv'       => $puzzle->theme_lv,
                'explanation'    => $attempt?->solved ? $puzzle->explanation : null,
                'explanation_lv' => $attempt?->solved ? $puzzle->explanation_lv : null,
                'difficulty'     => $puzzle->difficulty,
            ],
            'attempt' => $attempt ? [
                'solved'             => $attempt->solved,
                'attempts'           => $attempt->attempts,
                'solve_time_seconds' => $attempt->solve_time_seconds,
            ] : null,
            'leaderboard'   => $leaderboard,
            'total_solvers' => $totalSolvers,
            'total_attempts' => $totalAttempts,
        ]);
    }

    /**
     * POST /api/daily-puzzle/submit — submit an attempt.
     */
    public function submit(Request $request): JsonResponse
    {
        $request->validate([
            'move'       => 'required|string|max:10',
            'time_spent' => 'required|integer|min:0|max:86400',
        ]);

        $puzzle = DailyPuzzle::where('puzzle_date', Carbon::today()->toDateString())->first();

        if (!$puzzle) {
            return $this->error('No daily puzzle available today.', 404);
        }

        $userId = $request->user()->id;
        $attempt = DailyPuzzleAttempt::firstOrCreate(
            ['user_id' => $userId, 'daily_puzzle_id' => $puzzle->id],
            ['created_at' => now()],
        );

        if ($attempt->solved) {
            return $this->success('Already solved.', [
                'solved'       => true,
                'correct_move' => $puzzle->correct_move,
                'already'      => true,
            ]);
        }

        $attempt->attempts++;
        $isCorrect = strtolower(trim($request->input('move'))) === strtolower($puzzle->correct_move);

        if ($isCorrect) {
            $attempt->solved = true;
            $attempt->solve_time_seconds = $request->integer('time_spent');
        }

        $attempt->save();

        return $this->success($isCorrect ? 'Correct!' : 'Incorrect, try again.', [
            'solved'         => $isCorrect,
            'attempts'       => $attempt->attempts,
            'correct_move'   => $isCorrect ? $puzzle->correct_move : null,
            'explanation'    => $isCorrect ? $puzzle->explanation : null,
            'explanation_lv' => $isCorrect ? $puzzle->explanation_lv : null,
        ]);
    }

    /**
     * GET /api/daily-puzzle/history — user's history of daily puzzles.
     */
    public function history(Request $request): JsonResponse
    {
        $history = DailyPuzzleAttempt::where('user_id', $request->user()->id)
            ->with('puzzle:id,puzzle_date,theme,theme_lv,difficulty')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn($a) => [
                'date'       => $a->puzzle?->puzzle_date?->toDateString(),
                'theme'      => $a->puzzle?->theme,
                'theme_lv'   => $a->puzzle?->theme_lv,
                'difficulty'  => $a->puzzle?->difficulty,
                'solved'     => $a->solved,
                'attempts'   => $a->attempts,
                'time'       => $a->solve_time_seconds,
            ]);

        $streak = $this->calculateStreak($request->user()->id);

        return $this->success('History loaded.', [
            'history' => $history,
            'streak'  => $streak,
        ]);
    }

    private function calculateStreak(int $userId): int
    {
        $solved = DailyPuzzleAttempt::where('user_id', $userId)
            ->where('solved', true)
            ->join('daily_puzzles', 'daily_puzzles.id', '=', 'daily_puzzle_attempts.daily_puzzle_id')
            ->orderByDesc('daily_puzzles.puzzle_date')
            ->pluck('daily_puzzles.puzzle_date')
            ->map(fn($d) => Carbon::parse($d)->toDateString());

        if ($solved->isEmpty()) return 0;

        $streak = 0;
        $today = Carbon::today();

        foreach ($solved as $date) {
            $expected = $today->copy()->subDays($streak)->toDateString();
            if ($date === $expected) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }
}
