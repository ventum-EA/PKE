<?php

namespace App\Enums;

enum MoveClassification: string
{
    case BEST = 'best';
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case INACCURACY = 'inaccuracy';
    case MISTAKE = 'mistake';
    case BLUNDER = 'blunder';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
