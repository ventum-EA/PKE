<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyPuzzleAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'daily_puzzle_id', 'solved', 'attempts', 'solve_time_seconds', 'created_at',
    ];

    protected $casts = [
        'solved'             => 'boolean',
        'attempts'           => 'integer',
        'solve_time_seconds' => 'integer',
        'created_at'         => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function puzzle(): BelongsTo
    {
        return $this->belongsTo(DailyPuzzle::class, 'daily_puzzle_id');
    }
}
