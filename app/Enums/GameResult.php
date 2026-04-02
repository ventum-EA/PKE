<?php

namespace App\Enums;

enum GameResult: string
{
    case WHITE_WINS = '1-0';
    case BLACK_WINS = '0-1';
    case DRAW = '1/2-1/2';
    case ONGOING = '*';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
