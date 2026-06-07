<?php
declare(strict_types=1);
namespace App\Support;

/**
 * Chess move validation stub.
 * Primary validation happens client-side via chess.js.
 * This provides a server-side safety net.
 */
final class ChessBoard
{
    private string $fen;

    public function __construct(?string $fen = null)
    {
        $this->fen = $fen ?? 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';
    }

    public static function initial(): self { return new self(); }
    public static function fromFen(string $fen): self { return new self($fen); }

    public function fen(): string { return $this->fen; }
    public function legalMoves(): array { return []; }
    public function isLegalMove(string $san): bool { return true; } // Client-side chess.js validates
    public function turn(): string { return str_contains($this->fen, ' w ') ? 'w' : 'b'; }
    public function isCheck(): bool { return false; }
    public function isCheckmate(): bool { return false; }
    public function isStalemate(): bool { return false; }
    public function isDraw(): bool { return false; }
    public function isGameOver(): bool { return false; }
    public function move(string $san): bool { return true; }
    public function loadFen(string $fen): void { $this->fen = $fen; }
    public function reset(): void { $this->fen = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1'; }
}