<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrainingSession extends Model
{
    protected $fillable = [
        'user_id', 'game_id', 'fen', 'correct_move', 'user_move',
        'is_correct', 'category', 'hint',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function game() { return $this->belongsTo(Game::class); }

    public function getId(): int { return $this->getAttribute('id'); }
    public function getFen(): string { return $this->getAttribute('fen'); }
    public function getCorrectMove(): string { return $this->getAttribute('correct_move'); }
    public function getUserMove(): ?string { return $this->getAttribute('user_move'); }
    public function getIsCorrect(): ?bool { return $this->getAttribute('is_correct'); }
    public function getCategory(): string { return $this->getAttribute('category'); }
    public function getHint(): ?string { return $this->getAttribute('hint'); }
}
