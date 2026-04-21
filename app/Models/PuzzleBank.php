<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuzzleBank extends Model
{
    protected $table = 'puzzle_bank';

    protected $fillable = [
        'source_id', 'fen', 'solution', 'rating', 'themes',
        'opening_tags', 'difficulty', 'popularity',
    ];

    protected $casts = [
        'rating'     => 'integer',
        'difficulty' => 'integer',
        'popularity' => 'integer',
    ];

    public function attempts(): HasMany
    {
        return $this->hasMany(PuzzleAttempt::class, 'puzzle_id');
    }

    /** Get the first move of the solution (the answer). */
    public function getCorrectMoveAttribute(): string
    {
        return explode(' ', $this->solution)[0] ?? '';
    }

    /** Get themes as an array. */
    public function getThemeListAttribute(): array
    {
        return $this->themes ? array_map('trim', explode(',', $this->themes)) : [];
    }
}
