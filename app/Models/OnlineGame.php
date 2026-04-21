<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnlineGame extends Model
{
    protected $fillable = [
        'white_id', 'black_id', 'status', 'pgn', 'fen', 'result', 'result_reason',
        'invite_token', 'rated', 'opening_name', 'opening_eco', 'total_moves',
        'time_control', 'white_time_remaining', 'black_time_remaining',
        'white_elo_before', 'black_elo_before', 'white_elo_change', 'black_elo_change',
        'draw_offered_by', 'last_move_at',
    ];

    protected $casts = [
        'rated'      => 'boolean',
        'total_moves' => 'integer',
        'time_control' => 'integer',
        'white_time_remaining' => 'integer',
        'black_time_remaining' => 'integer',
        'last_move_at' => 'datetime',
    ];

    public function white(): BelongsTo
    {
        return $this->belongsTo(User::class, 'white_id');
    }

    public function black(): BelongsTo
    {
        return $this->belongsTo(User::class, 'black_id');
    }

    public function moves(): HasMany
    {
        return $this->hasMany(OnlineGameMove::class)->orderBy('move_number');
    }

    public function isPlayerTurn(int $userId): bool
    {
        $turnColor = str_contains($this->fen, ' w ') ? 'white' : 'black';
        return ($turnColor === 'white' && $this->white_id === $userId)
            || ($turnColor === 'black' && $this->black_id === $userId);
    }

    public function getPlayerColor(int $userId): ?string
    {
        if ($this->white_id === $userId) return 'white';
        if ($this->black_id === $userId) return 'black';
        return null;
    }

    public function getOpponentId(int $userId): ?int
    {
        if ($this->white_id === $userId) return $this->black_id;
        if ($this->black_id === $userId) return $this->white_id;
        return null;
    }
}
