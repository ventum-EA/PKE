<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonPuzzle extends Model
{
    protected $fillable = [
        'lesson_id', 'fen', 'correct_move', 'explanation_lv', 'hints_lv', 'sort_order',
    ];

    protected $casts = [
        'hints_lv' => 'array',
    ];

    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
