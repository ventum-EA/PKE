<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class GameMoveData extends Data
{
    public function __construct(
        public int $game_id,
        public int $move_number,
        public string $color,
        public string $move_san,
        public ?string $move_uci = null,
        public ?string $fen_before = null,
        public ?string $fen_after = null,
        public ?float $eval_before = null,
        public ?float $eval_after = null,
        public ?float $eval_diff = null,
        public ?string $best_move = null,
        public ?string $classification = null,
        public ?string $error_category = null,
        public ?string $explanation = null,
    ) {}
}
