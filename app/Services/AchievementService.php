<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Achievement;
use App\Models\DailyPuzzleAttempt;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

class AchievementService
{
    public function __construct(
        protected ConnectionInterface $db,
    ) {}

    /**
     * Check all achievements for a user and award any newly earned ones.
     * Returns an array of newly unlocked achievement slugs.
     */
    public function checkAndAward(int $userId): array
    {
        $achievements = Achievement::all();
        $existing = $this->db->table('user_achievements')
            ->where('user_id', $userId)
            ->pluck('progress', 'achievement_id')
            ->toArray();

        $unlocked = $this->db->table('user_achievements')
            ->where('user_id', $userId)
            ->where('unlocked', true)
            ->pluck('achievement_id')
            ->toArray();

        // Pre-compute all progress counts in bulk (one query per metric, not per achievement)
        $counts = $this->bulkComputeProgress($userId);

        $newlyUnlocked = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement->id, $unlocked)) {
                continue;
            }

            $progress = $counts[$achievement->slug] ?? 0;

            // Upsert progress
            $exists = isset($existing[$achievement->id]);
            $values = [
                'progress'    => $progress,
                'unlocked'    => $progress >= $achievement->threshold,
                'unlocked_at' => $progress >= $achievement->threshold ? now() : null,
                'updated_at'  => now(),
            ];
            if (!$exists) {
                $values['created_at'] = now();
            }

            $this->db->table('user_achievements')->updateOrInsert(
                ['user_id' => $userId, 'achievement_id' => $achievement->id],
                $values,
            );

            if ($progress >= $achievement->threshold) {
                $newlyUnlocked[] = [
                    'slug'    => $achievement->slug,
                    'name'    => $achievement->name,
                    'name_lv' => $achievement->name_lv,
                    'icon'    => $achievement->icon,
                    'tier'    => $achievement->tier,
                ];
            }
        }

        return $newlyUnlocked;
    }

    /**
     * Get all achievements with user progress.
     */
    public function getUserAchievements(int $userId): array
    {
        return Achievement::query()
            ->leftJoin('user_achievements', function ($join) use ($userId) {
                $join->on('achievements.id', '=', 'user_achievements.achievement_id')
                    ->where('user_achievements.user_id', $userId);
            })
            ->select([
                'achievements.*',
                $this->db->raw('COALESCE(user_achievements.progress, 0) as user_progress'),
                $this->db->raw('COALESCE(user_achievements.unlocked, 0) as user_unlocked'),
                'user_achievements.unlocked_at',
            ])
            ->orderBy('achievements.sort_order')
            ->get()
            ->toArray();
    }

    /**
     * Pre-compute all progress values with minimal queries.
     * Instead of 22 separate queries (one per achievement), runs ~8.
     */
    private function bulkComputeProgress(int $userId): array
    {
        $games    = $this->countGames($userId);
        $wins     = $this->countWins($userId);
        $analyzed = $this->countAnalyzed($userId);
        $puzzles  = $this->countSolvedPuzzles($userId);
        $streak   = $this->calculateDailyStreak($userId);
        $openings = $this->countPracticedOpenings($userId);
        $accuracy = $this->bestAccuracy($userId);
        $daily    = $this->countDailySolves($userId);

        return [
            'first_game'     => $games,
            'games_10'       => $games,
            'games_50'       => $games,
            'games_100'      => $games,
            'first_win'      => $wins,
            'wins_10'        => $wins,
            'wins_25'        => $wins,
            'first_analysis' => $analyzed,
            'analyzed_10'    => $analyzed,
            'analyzed_25'    => $analyzed,
            'puzzles_10'     => $puzzles,
            'puzzles_50'     => $puzzles,
            'puzzles_100'    => $puzzles,
            'streak_3'       => $streak,
            'streak_7'       => $streak,
            'streak_30'      => $streak,
            'openings_5'     => $openings,
            'openings_20'    => $openings,
            'accuracy_90'    => $accuracy,
            'daily_first'    => $daily,
            'daily_10'       => $daily,
        ];
    }

    private function calculateProgress(int $userId, Achievement $achievement): int
    {
        return match ($achievement->slug) {
            // Game milestones
            'first_game'     => $this->countGames($userId),
            'games_10'       => $this->countGames($userId),
            'games_50'       => $this->countGames($userId),
            'games_100'      => $this->countGames($userId),

            // Win milestones
            'first_win'      => $this->countWins($userId),
            'wins_10'        => $this->countWins($userId),
            'wins_25'        => $this->countWins($userId),

            // Analysis milestones
            'first_analysis' => $this->countAnalyzed($userId),
            'analyzed_10'    => $this->countAnalyzed($userId),
            'analyzed_25'    => $this->countAnalyzed($userId),

            // Training milestones
            'puzzles_10'     => $this->countSolvedPuzzles($userId),
            'puzzles_50'     => $this->countSolvedPuzzles($userId),
            'puzzles_100'    => $this->countSolvedPuzzles($userId),

            // Streaks
            'streak_3'       => $this->calculateDailyStreak($userId),
            'streak_7'       => $this->calculateDailyStreak($userId),
            'streak_30'      => $this->calculateDailyStreak($userId),

            // Opening mastery
            'openings_5'     => $this->countPracticedOpenings($userId),
            'openings_20'    => $this->countPracticedOpenings($userId),

            // Accuracy
            'accuracy_90'    => $this->bestAccuracy($userId),

            // Daily puzzle
            'daily_first'    => $this->countDailySolves($userId),
            'daily_10'       => $this->countDailySolves($userId),

            default => 0,
        };
    }

    private function countGames(int $userId): int
    {
        return (int) $this->db->table('games')
            ->where('user_id', $userId)->whereNull('deleted_at')->count();
    }

    private function countWins(int $userId): int
    {
        return (int) $this->db->table('games')
            ->where('user_id', $userId)->whereNull('deleted_at')
            ->where(function ($q) {
                $q->where(fn($q2) => $q2->where('result', '1-0')->where('user_color', 'white'))
                  ->orWhere(fn($q2) => $q2->where('result', '0-1')->where('user_color', 'black'));
            })->count();
    }

    private function countAnalyzed(int $userId): int
    {
        return (int) $this->db->table('games')
            ->where('user_id', $userId)->where('is_analyzed', true)->whereNull('deleted_at')->count();
    }

    private function countSolvedPuzzles(int $userId): int
    {
        return (int) $this->db->table('training_sessions')
            ->where('user_id', $userId)->where('is_correct', true)->count();
    }

    private function calculateDailyStreak(int $userId): int
    {
        $dates = $this->db->table('games')
            ->where('user_id', $userId)->whereNull('deleted_at')
            ->selectRaw('DATE(created_at) as d')
            ->union(
                $this->db->table('training_sessions')
                    ->where('user_id', $userId)
                    ->selectRaw('DATE(created_at) as d')
            )
            ->orderByDesc('d')
            ->pluck('d')
            ->unique()
            ->values();

        if ($dates->isEmpty()) return 0;

        $streak = 1;
        $maxStreak = 1;

        for ($i = 1; $i < $dates->count(); $i++) {
            $prev = Carbon::parse($dates[$i - 1]);
            $curr = Carbon::parse($dates[$i]);
            if ($prev->diffInDays($curr) === 1) {
                $streak++;
                $maxStreak = max($maxStreak, $streak);
            } else {
                $streak = 1;
            }
        }

        return $maxStreak;
    }

    private function countPracticedOpenings(int $userId): int
    {
        return (int) $this->db->table('user_opening_progress')
            ->where('user_id', $userId)->where('completed', true)->count();
    }

    private function bestAccuracy(int $userId): int
    {
        // Check if user has any game with >= 90% accuracy (best/excellent/good moves)
        $games = $this->db->table('games')
            ->where('user_id', $userId)->where('is_analyzed', true)
            ->whereNull('deleted_at')
            ->pluck('id');

        foreach ($games as $gameId) {
            $total = $this->db->table('game_moves')->where('game_id', $gameId)->count();
            if ($total < 10) continue;

            $good = $this->db->table('game_moves')
                ->where('game_id', $gameId)
                ->whereIn('classification', ['best', 'excellent', 'good'])
                ->count();

            $accuracy = ($good / $total) * 100;
            if ($accuracy >= 90) return 1;
        }

        return 0;
    }

    private function countDailySolves(int $userId): int
    {
        return (int) DailyPuzzleAttempt::where('user_id', $userId)
            ->where('solved', true)->count();
    }
}
