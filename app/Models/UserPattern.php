<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPattern extends Model
{
    protected $fillable = [
        'user_id', 'pattern_type', 'description', 'description_lv',
        'occurrences', 'severity', 'suggestion', 'suggestion_lv',
        'evidence', 'detected_at',
    ];

    protected $casts = [
        'evidence'    => 'array',
        'occurrences' => 'integer',
        'severity'    => 'integer',
        'detected_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
