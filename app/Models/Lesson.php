<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lesson extends Model
{
    protected $fillable = [
        'slug', 'category', 'title', 'title_lv', 'description_lv',
        'difficulty', 'theory_lv', 'icon', 'color', 'sort_order',
    ];

    public function puzzles()
    {
        return $this->hasMany(LessonPuzzle::class)->orderBy('sort_order');
    }

    public function userProgress()
    {
        return $this->hasMany(UserLessonProgress::class);
    }
}
