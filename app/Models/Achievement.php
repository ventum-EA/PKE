<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Achievement extends Model
{
    public const SLUG        = 'slug';
    public const NAME        = 'name';
    public const NAME_LV     = 'name_lv';
    public const CATEGORY    = 'category';
    public const TIER        = 'tier';
    public const THRESHOLD   = 'threshold';
    public const ICON        = 'icon';
    public const SORT_ORDER  = 'sort_order';

    protected $fillable = [
        'slug', 'name', 'name_lv', 'description', 'description_lv',
        'icon', 'category', 'tier', 'threshold', 'sort_order',
    ];

    protected $casts = [
        'threshold'  => 'integer',
        'sort_order' => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_achievements')
            ->withPivot('progress', 'unlocked', 'unlocked_at')
            ->withTimestamps();
    }
}
