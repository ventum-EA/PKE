<?php

namespace App\Enums;

enum ErrorCategory: string
{
    case TACTICAL = 'tactical';
    case POSITIONAL = 'positional';
    case OPENING = 'opening';
    case ENDGAME = 'endgame';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
