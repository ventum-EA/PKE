<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLessonProgress extends Model
{
    protected $table = 'user_lesson_progress';

    protected $fillable = [
        'user_id', 'lesson_id', 'completed', 'puzzles_solved',
        'puzzles_total', 'best_score', 'last_attempted_at',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'last_attempted_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function lesson() { return $this->belongsTo(Lesson::class); }
}
