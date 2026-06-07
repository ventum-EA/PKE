<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Parses PGN (Portable Game Notation) strings into move token arrays
 * and builds fully-resolved move structures with real FEN positions.
 *
 * Handles comments, variations, move numbers, and result markers.
 * This class replaces the duplicated parsePgnMoves() / extractFens()
 * logic that previously lived in both GameService and AnalyzeGameJob.
 */
final class PgnParser
{
    private const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    public static function extractMoves(string $pgn): array
    {
        $clean = preg_replace('/\{[^}]*\}/', '', $pgn);       // Remove comments
        $clean = preg_replace('/\([^)]*\)/', '', $clean);       // Remove variations
        $clean = preg_replace('/\d+\.\.\./', '', $clean);       // Remove continuation dots
        $clean = preg_replace('/\d+\./', '', $clean);           // Remove move numbers
        $clean = preg_replace('/(1-0|0-1|1\/2-1\/2|\*)\s*$/', '', $clean); // Remove result

        $tokens = preg_split('/\s+/', trim($clean));

        return array_values(array_filter($tokens, static fn(string $t): bool => $t !== ''));
    }

    /**
     * Build a structured array of move data with real FEN positions
     * computed via the ChessBoard engine. Each entry contains the SAN,
     * color, move number, and the actual FEN after that move.
     *
     * The first element (index 0) is always the starting position.
     * If a move is illegal (corrupted PGN), parsing stops at that point
     * and the partial result is returned.
     *
     * @return array<int, array{fen: string, san: string, uci: ?string, moveNumber: int, color: string}>
     */
    public static function buildMoveStructures(string $pgn): array
    {
        $tokens = self::extractMoves($pgn);
        $board  = ChessBoard::initial();

        $structures = [];

        // Starting position
        $structures[] = [
            'fen'        => self::START_FEN,
            'san'        => '',
            'uci'        => null,
            'moveNumber' => 0,
            'color'      => 'white',
        ];

        foreach ($tokens as $i => $move) {
            if (!$board->move($move)) {
                // Illegal move in PGN — stop parsing to avoid corrupt data.
                // The caller can check count($structures) < count($tokens)+1
                // to detect truncation.
                break;
            }

            $structures[] = [
                'fen'        => $board->fen(),
                'san'        => $move,
                'uci'        => null,
                'moveNumber' => intdiv($i, 2) + 1,
                'color'      => $i % 2 === 0 ? 'white' : 'black',
            ];
        }

        return $structures;
    }

    public static function startFen(): string
    {
        return self::START_FEN;
    }
}
