<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameImport extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'source', 'source_username', 'source_game_id',
        'game_id', 'imported_at',
    ];

    protected $casts = ['imported_at' => 'datetime'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function game(): BelongsTo { return $this->belongsTo(Game::class); }
}
