<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineGameMove extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'online_game_id', 'move_number', 'color', 'move_san', 'move_uci', 'fen_after',
    ];

    protected $casts = [
        'move_number' => 'integer',
        'created_at'  => 'datetime',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(OnlineGame::class, 'online_game_id');
    }
}
