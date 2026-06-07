<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\DB;

class EloService
{
    /**
     * Stockfish skill level (0-20) → approximate ELO rating.
     * Based on Stockfish documentation and empirical testing.
     */
    private const SKILL_TO_ELO = [
        0  => 400,   1  => 500,   2  => 600,   3  => 700,
        4  => 850,   5  => 1000,  6  => 1150,  7  => 1300,
        8  => 1400,  9  => 1500,  10 => 1600,  11 => 1700,
        12 => 1800,  13 => 1900,  14 => 2000,  15 => 2100,
        16 => 2200,  17 => 2350,  18 => 2500,  19 => 2650,
        20 => 2800,
    ];

    private const K_FACTOR_NEW     = 40;  // First 30 games
    private const K_FACTOR_DEFAULT = 20;  // 30+ games
    private const K_FACTOR_HIGH    = 10;  // ELO >= 2400

    private const NEW_PLAYER_THRESHOLD  = 30;
    private const HIGH_RATING_THRESHOLD = 2400;

    private const MIN_ELO = 100;
    private const MAX_ELO = 3000;

    private const TRAINING_BASE_ELO        = 3;
    private const TRAINING_ACCURACY_BONUS  = 5;
    private const TRAINING_DIFFICULTY_MULT = [
        'easy'   => 0.5,
        'medium' => 1.0,
        'hard'   => 1.5,
    ];
    private const TRAINING_CATEGORY_MULT = [
        'tactical'   => 1.2,
        'positional' => 1.0,
        'opening'    => 0.8,
        'endgame'    => 1.1,
    ];
    private const TRAINING_MAX_ELO_GAIN = 15;

    public function __construct(
        protected UserRepository $userRepo,
        protected Guard $auth,
    ) {}

    /**
     * Calculate and apply ELO change after a game against Stockfish.
     *
     * @param float $score  1.0 = win, 0.5 = draw, 0.0 = loss
     * @return array{old_elo: int, new_elo: int, change: int, opponent_elo: int}
     */
    public function processGameResult(int $userId, int $skillLevel, float $score): array
    {
        $user = $this->userRepo->findById($userId);
        $playerElo = (int) $user->elo_rating;
        $opponentElo = $this->skillToElo($skillLevel);
        $gamesPlayed = $this->getGamesPlayed($userId);

        $kFactor = $this->getKFactor($playerElo, $gamesPlayed);
        $expected = $this->expectedScore($playerElo, $opponentElo);
        $change = (int) round($kFactor * ($score - $expected));

        $newElo = $this->clampElo($playerElo + $change);

        $this->userRepo->update($user, ['elo_rating' => $newElo]);

        $this->logEloChange($userId, $playerElo, $newElo, $change, 'game', [
            'opponent_elo' => $opponentElo,
            'skill_level'  => $skillLevel,
            'score'        => $score,
            'k_factor'     => $kFactor,
        ]);

        return [
            'old_elo'      => $playerElo,
            'new_elo'      => $newElo,
            'change'       => $change,
            'opponent_elo' => $opponentElo,
        ];
    }

    /**
     * Calculate and apply ELO reward for completing training puzzles.
     *
     * @return array{old_elo: int, new_elo: int, change: int}
     */
    public function processTrainingResult(
        int $userId,
        int $correct,
        int $total,
        string $category = 'tactical',
        string $difficulty = 'medium',
    ): array {
        $user = $this->userRepo->findById($userId);
        $playerElo = (int) $user->elo_rating;

        if ($total <= 0 || $correct <= 0) {
            return ['old_elo' => $playerElo, 'new_elo' => $playerElo, 'change' => 0];
        }

        $accuracy = $correct / $total;

        $base = self::TRAINING_BASE_ELO * $correct;
        $accuracyBonus = $accuracy >= 0.8 ? self::TRAINING_ACCURACY_BONUS : 0;
        $diffMult = self::TRAINING_DIFFICULTY_MULT[$difficulty] ?? 1.0;
        $catMult = self::TRAINING_CATEGORY_MULT[$category] ?? 1.0;

        $change = (int) round(($base + $accuracyBonus) * $diffMult * $catMult);
        $change = min($change, self::TRAINING_MAX_ELO_GAIN);

        // No ELO loss from training — only gains
        if ($change <= 0) {
            return ['old_elo' => $playerElo, 'new_elo' => $playerElo, 'change' => 0];
        }

        $newElo = $this->clampElo($playerElo + $change);

        $this->userRepo->update($user, ['elo_rating' => $newElo]);

        $this->logEloChange($userId, $playerElo, $newElo, $change, 'training', [
            'correct'    => $correct,
            'total'      => $total,
            'accuracy'   => round($accuracy * 100, 1),
            'category'   => $category,
            'difficulty' => $difficulty,
        ]);

        return [
            'old_elo' => $playerElo,
            'new_elo' => $newElo,
            'change'  => $change,
        ];
    }

    /**
     * Calculate and apply ELO changes for a multiplayer game.
     *
     * Uses the same K-factor brackets as single-player to keep
     * ratings consistent across all game modes.
     *
     * @param float $whiteScore 1.0 = white wins, 0.5 = draw, 0.0 = black wins
     * @return array{white_change: int, black_change: int}
     */
    public function processMultiplayerResult(
        int $whiteId,
        int $blackId,
        float $whiteScore,
        int $gameId,
    ): array {
        $white = $this->userRepo->findById($whiteId);
        $black = $this->userRepo->findById($blackId);

        $whiteElo = (int) $white->elo_rating;
        $blackElo = (int) $black->elo_rating;
        $blackScore = 1.0 - $whiteScore;

        $whiteGames = $this->getGamesPlayed($whiteId);
        $blackGames = $this->getGamesPlayed($blackId);

        $whiteK = $this->getKFactor($whiteElo, $whiteGames);
        $blackK = $this->getKFactor($blackElo, $blackGames);

        $whiteExpected = $this->expectedScore($whiteElo, $blackElo);
        $blackExpected = $this->expectedScore($blackElo, $whiteElo);

        $whiteChange = (int) round($whiteK * ($whiteScore - $whiteExpected));
        $blackChange = (int) round($blackK * ($blackScore - $blackExpected));

        $whiteNewElo = $this->clampElo($whiteElo + $whiteChange);
        $blackNewElo = $this->clampElo($blackElo + $blackChange);

        $this->userRepo->update($white, ['elo_rating' => $whiteNewElo]);
        $this->userRepo->update($black, ['elo_rating' => $blackNewElo]);

        foreach ([
            [$whiteId, $whiteElo, $whiteNewElo, $whiteChange, $blackElo],
            [$blackId, $blackElo, $blackNewElo, $blackChange, $whiteElo],
        ] as [$uid, $old, $new, $change, $oppElo]) {
            $this->logEloChange($uid, $old, $new, $change, 'multiplayer', [
                'game_id'      => $gameId,
                'opponent_elo' => $oppElo,
                'k_factor'     => $uid === $whiteId ? $whiteK : $blackK,
            ]);
        }

        return [
            'white_change' => $whiteChange,
            'black_change' => $blackChange,
        ];
    }

    /**
     * Get ELO change history for a user.
     */
    public function getHistory(int $userId, int $limit = 50): array
    {
        return DB::table('elo_history')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get the ELO rating for a Stockfish skill level.
     */
    public static function skillToElo(int $skillLevel): int
    {
        $clamped = max(0, min(20, $skillLevel));
        return self::SKILL_TO_ELO[$clamped] ?? 1600;
    }

    /**
     * Expected score using the standard ELO formula.
     * E(A) = 1 / (1 + 10^((Rb - Ra) / 400))
     */
    private function expectedScore(int $playerElo, int $opponentElo): float
    {
        return 1.0 / (1.0 + pow(10, ($opponentElo - $playerElo) / 400.0));
    }

    private function getKFactor(int $elo, int $gamesPlayed): int
    {
        if ($gamesPlayed < self::NEW_PLAYER_THRESHOLD) {
            return self::K_FACTOR_NEW;
        }
        if ($elo >= self::HIGH_RATING_THRESHOLD) {
            return self::K_FACTOR_HIGH;
        }
        return self::K_FACTOR_DEFAULT;
    }

    private function clampElo(int $elo): int
    {
        return max(self::MIN_ELO, min(self::MAX_ELO, $elo));
    }

    private function getGamesPlayed(int $userId): int
    {
        return (int) DB::table('games')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->count();
    }

    private function logEloChange(int $userId, int $oldElo, int $newElo, int $change, string $source, array $meta = []): void
    {
        DB::table('elo_history')->insert([
            'user_id'    => $userId,
            'old_elo'    => $oldElo,
            'new_elo'    => $newElo,
            'change'     => $change,
            'source'     => $source,
            'meta'       => json_encode($meta),
            'created_at' => now(),
        ]);
    }
}
