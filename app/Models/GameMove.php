<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMove extends Model
{
    public const ID = 'id';
    public const GAME_ID = 'game_id';
    public const MOVE_NUMBER = 'move_number';
    public const COLOR = 'color';
    public const MOVE_SAN = 'move_san';
    public const MOVE_UCI = 'move_uci';
    public const FEN_BEFORE = 'fen_before';
    public const FEN_AFTER = 'fen_after';
    public const EVAL_BEFORE = 'eval_before';
    public const EVAL_AFTER = 'eval_after';
    public const EVAL_DIFF = 'eval_diff';
    public const BEST_MOVE = 'best_move';
    public const CLASSIFICATION = 'classification';
    public const ERROR_CATEGORY = 'error_category';
    public const EXPLANATION = 'explanation';

    protected $fillable = [
        self::GAME_ID, self::MOVE_NUMBER, self::COLOR, self::MOVE_SAN, self::MOVE_UCI,
        self::FEN_BEFORE, self::FEN_AFTER, self::EVAL_BEFORE, self::EVAL_AFTER,
        self::EVAL_DIFF, self::BEST_MOVE, self::CLASSIFICATION, self::ERROR_CATEGORY,
        self::EXPLANATION,
    ];

    protected $casts = [
        'eval_before' => 'float',
        'eval_after' => 'float',
        'eval_diff' => 'float',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function getId(): int { return $this->getAttribute(self::ID); }
    public function getGameId(): int { return $this->getAttribute(self::GAME_ID); }
    public function getMoveNumber(): int { return $this->getAttribute(self::MOVE_NUMBER); }
    public function getColor(): string { return $this->getAttribute(self::COLOR); }
    public function getMoveSan(): string { return $this->getAttribute(self::MOVE_SAN); }
    public function getClassification(): ?string { return $this->getAttribute(self::CLASSIFICATION); }
    public function getErrorCategory(): ?string { return $this->getAttribute(self::ERROR_CATEGORY); }
    public function getExplanation(): ?string { return $this->getAttribute(self::EXPLANATION); }
    public function getEvalDiff(): ?float { return $this->getAttribute(self::EVAL_DIFF); }
    public function getBestMove(): ?string { return $this->getAttribute(self::BEST_MOVE); }
    public function getFenBefore(): ?string { return $this->getAttribute(self::FEN_BEFORE); }
    public function getFenAfter(): ?string { return $this->getAttribute(self::FEN_AFTER); }
    public function getEvalBefore(): ?float { return $this->getAttribute(self::EVAL_BEFORE); }
    public function getEvalAfter(): ?float { return $this->getAttribute(self::EVAL_AFTER); }
}
