<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opening extends Model
{
    protected $fillable = [
        'eco', 'category', 'name', 'name_lv', 'moves',
        'summary_lv', 'ideas_lv', 'move_explanations_lv', 'sort_order',
    ];

    protected $casts = [
        'ideas_lv' => 'array',
        'move_explanations_lv' => 'array',
    ];

    public function userProgress()
    {
        return $this->hasMany(UserOpeningProgress::class);
    }
}
