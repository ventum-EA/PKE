<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PuzzleAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'puzzle_id', 'solved', 'attempts'];

    protected $casts = [
        'solved'     => 'boolean',
        'attempts'   => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function puzzle(): BelongsTo { return $this->belongsTo(PuzzleBank::class, 'puzzle_id'); }
}
