<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAnnotation extends Model
{
    protected $fillable = [
        'game_id', 'user_id', 'move_index', 'comment', 'arrows', 'highlights',
    ];

    protected $casts = [
        'move_index' => 'integer',
        'arrows'     => 'array',
        'highlights' => 'array',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
