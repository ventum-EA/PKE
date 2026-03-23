<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;

class GameData extends Data
{
    public function __construct(
        #[Required]
        public string $pgn,

        public ?string $white_player = null,
        public ?string $black_player = null,
        public ?string $result = '*',
        public ?string $opening_name = null,
        public ?string $opening_eco = null,
        public ?int $total_moves = 0,
        public ?string $user_color = 'white',
        public ?int $user_id = null,
        public ?int $game_id = null,
        public ?string $played_at = null,
    ) {}
}
