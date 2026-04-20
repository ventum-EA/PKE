<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ErrorCategory;
use App\Enums\MoveClassification;

/**
 * Chess analysis utilities — single source of truth for move evaluation.
 *
 * Thresholds align with chess.com / lichess standards:
 *   Best       ≤ 0.05 pawns lost
 *   Excellent  ≤ 0.15
 *   Good       ≤ 0.35
 *   Inaccuracy ≤ 0.90
 *   Mistake    ≤ 2.50
 *   Blunder    > 2.50
 */
final class ChessAnalyzer
{
    /** @var array<string, float> Classification thresholds (upper bound, ascending) */
    private const THRESHOLDS = [
        'best'       => 0.05,
        'excellent'  => 0.15,
        'good'       => 0.35,
        'inaccuracy' => 0.90,
        'mistake'    => 2.50,
    ];

    private const ERROR_CLASSIFICATIONS = [
        MoveClassification::INACCURACY->value,
        MoveClassification::MISTAKE->value,
        MoveClassification::BLUNDER->value,
    ];

    private const OPENING_PIECE_THRESHOLD = 12;
    private const OPENING_HALF_MOVE_MAX   = 24;
    private const ENDGAME_PIECE_THRESHOLD = 10;

    /**
     * Classify eval loss into a MoveClassification.
     *
     * @param float  $evalBefore  Eval from White's perspective before the move
     * @param float  $evalAfter   Eval from White's perspective after the move
     * @param string $color       'white' or 'black'
     */
    public static function classify(float $evalBefore, float $evalAfter, string $color): MoveClassification
    {
        $loss = $color === 'white'
            ? $evalBefore - $evalAfter
            : $evalAfter - $evalBefore;

        foreach (self::THRESHOLDS as $label => $threshold) {
            if ($loss <= $threshold) {
                return MoveClassification::from($label);
            }
        }

        return MoveClassification::BLUNDER;
    }

    public static function categorize(int $halfMoveIndex, int $totalHalfMoves, string $san, ?string $fen = null): ErrorCategory
    {
        $phase = self::detectPhase($halfMoveIndex, $fen);

        if ($phase === 'opening') {
            return ErrorCategory::OPENING;
        }
        if ($phase === 'endgame') {
            return ErrorCategory::ENDGAME;
        }

        if (str_contains($san, 'x') || str_contains($san, '+') || str_contains($san, '#')) {
            return ErrorCategory::TACTICAL;
        }
        if (str_contains($san, 'O-O')) {
            return ErrorCategory::TACTICAL;
        }

        return ErrorCategory::POSITIONAL;
    }

    public static function isError(MoveClassification $classification): bool
    {
        return in_array($classification->value, self::ERROR_CLASSIFICATIONS, true);
    }

    public static function errorClassificationValues(): array
    {
        return self::ERROR_CLASSIFICATIONS;
    }

    private static function detectPhase(int $halfMoveIndex, ?string $fen): string
    {
        if ($fen) {
            $pieces = self::countPieces($fen);
            $minorMajor = self::countMinorMajor($fen);

            if ($halfMoveIndex < self::OPENING_HALF_MOVE_MAX && $minorMajor >= self::OPENING_PIECE_THRESHOLD) {
                return 'opening';
            }
            if ($pieces <= self::ENDGAME_PIECE_THRESHOLD) {
                return 'endgame';
            }
            if (!self::hasQueens($fen) && $minorMajor <= 4) {
                return 'endgame';
            }

            return 'middlegame';
        }

        // Fallback: ratio-based when no FEN available
        if ($halfMoveIndex < self::OPENING_HALF_MOVE_MAX) {
            return 'opening';
        }

        return 'middlegame';
    }

    private static function countPieces(string $fen): int
    {
        $board = explode(' ', $fen)[0];
        return preg_match_all('/[pnbrqkPNBRQK]/', $board);
    }

    private static function countMinorMajor(string $fen): int
    {
        $board = explode(' ', $fen)[0];
        return preg_match_all('/[nbrqNBRQ]/', $board);
    }

    private static function hasQueens(string $fen): bool
    {
        $board = explode(' ', $fen)[0];
        return str_contains($board, 'q') || str_contains($board, 'Q');
    }
}
