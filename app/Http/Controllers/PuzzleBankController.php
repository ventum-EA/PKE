<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\PuzzleAttempt;
use App\Models\PuzzleBank;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PuzzleBankController extends Controller
{
    use ApiResponse;

    /**
     * GET /api/puzzle-bank/next — get the next puzzle for the user.
     * Prioritizes: unseen puzzles matching user's skill, then failed puzzles for retry.
     */
    public function next(Request $request): JsonResponse
    {
        $request->validate([
            'theme'      => 'nullable|string|max:40',
            'difficulty' => 'nullable|integer|in:1,2,3',
        ]);

        $userId = $request->user()->id;
        $userElo = (int) ($request->user()->elo_rating ?? config('chess.elo.default', 1200));

        // Target puzzle rating: ±300 of user's ELO
        $minRating = max(400, $userElo - 300);
        $maxRating = min(2800, $userElo + 300);

        $query = PuzzleBank::query()
            ->whereBetween('rating', [$minRating, $maxRating]);

        if ($request->filled('theme')) {
            $query->where('themes', 'LIKE', '%' . $request->input('theme') . '%');
        }
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->integer('difficulty'));
        }

        // Prefer unseen puzzles
        $attemptedIds = PuzzleAttempt::where('user_id', $userId)->pluck('puzzle_id');

        $puzzle = $query->whereNotIn('id', $attemptedIds)
            ->inRandomOrder()
            ->first();

        // Fallback: retry a failed puzzle
        if (!$puzzle) {
            $failedIds = PuzzleAttempt::where('user_id', $userId)
                ->where('solved', false)
                ->pluck('puzzle_id');

            $puzzle = PuzzleBank::whereIn('id', $failedIds)
                ->inRandomOrder()
                ->first();
        }

        if (!$puzzle) {
            return $this->error('No puzzles available. Try adjusting filters.', 404);
        }

        return $this->success('Puzzle loaded.', [
            'puzzle' => [
                'id'         => $puzzle->id,
                'fen'        => $puzzle->fen,
                'rating'     => $puzzle->rating,
                'themes'     => $puzzle->theme_list,
                'difficulty' => $puzzle->difficulty,
            ],
        ]);
    }

    /**
     * POST /api/puzzle-bank/{id}/submit — submit a puzzle attempt.
     */
    public function submit(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'move' => 'required|string|max:10',
        ]);

        $puzzle = PuzzleBank::findOrFail($id);
        $userId = $request->user()->id;

        $attempt = PuzzleAttempt::firstOrCreate(
            ['user_id' => $userId, 'puzzle_id' => $id],
            ['created_at' => now()],
        );

        if ($attempt->solved) {
            return $this->success('Already solved.', [
                'solved'   => true,
                'solution' => $puzzle->solution,
                'already'  => true,
            ]);
        }

        $attempt->attempts++;
        $userMove = strtolower(trim($request->input('move')));
        $solutionMoves = explode(' ', $puzzle->solution);
        $correctMove = strtolower($solutionMoves[0] ?? '');

        $isCorrect = $userMove === $correctMove;

        if ($isCorrect) {
            $attempt->solved = true;
        }

        $attempt->save();

        return $this->success($isCorrect ? 'Correct!' : 'Incorrect.', [
            'solved'    => $isCorrect,
            'attempts'  => $attempt->attempts,
            'solution'  => $isCorrect ? $puzzle->solution : null,
            'themes'    => $isCorrect ? $puzzle->theme_list : null,
        ]);
    }

    /**
     * GET /api/puzzle-bank/stats — user's puzzle solving stats.
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $total = PuzzleAttempt::where('user_id', $userId)->count();
        $solved = PuzzleAttempt::where('user_id', $userId)->where('solved', true)->count();
        $bankSize = PuzzleBank::count();

        // Theme breakdown
        $themes = PuzzleAttempt::where('user_id', $userId)
            ->where('solved', true)
            ->join('puzzle_bank', 'puzzle_bank.id', '=', 'puzzle_attempts.puzzle_id')
            ->selectRaw("puzzle_bank.themes")
            ->get()
            ->flatMap(fn($r) => explode(',', $r->themes ?? ''))
            ->map(fn($t) => trim($t))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->toArray();

        return $this->success('Puzzle stats.', [
            'total_attempted' => $total,
            'total_solved'    => $solved,
            'bank_size'       => $bankSize,
            'accuracy'        => $total > 0 ? round(($solved / $total) * 100) : 0,
            'top_themes'      => $themes,
        ]);
    }

    /**
     * GET /api/puzzle-bank/themes — available themes with counts.
     */
    public function themes(): JsonResponse
    {
        $themes = PuzzleBank::selectRaw("themes")
            ->limit(5000)
            ->get()
            ->flatMap(fn($r) => explode(',', $r->themes ?? ''))
            ->map(fn($t) => trim($t))
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(30)
            ->toArray();

        return $this->success('Themes loaded.', ['themes' => $themes]);
    }
}
