<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyPuzzle extends Model
{
    public const PUZZLE_DATE   = 'puzzle_date';
    public const FEN           = 'fen';
    public const CORRECT_MOVE  = 'correct_move';
    public const THEME         = 'theme';
    public const DIFFICULTY    = 'difficulty';

    protected $fillable = [
        'puzzle_date', 'fen', 'correct_move', 'theme', 'theme_lv',
        'explanation', 'explanation_lv', 'difficulty',
    ];

    protected $casts = [
        'puzzle_date' => 'date',
        'difficulty'  => 'integer',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(DailyPuzzleAttempt::class);
    }
}
