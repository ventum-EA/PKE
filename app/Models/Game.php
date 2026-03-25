<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    public const ID = 'id';
    public const PGN = 'pgn';
    public const USER_ID = 'user_id';
    public const WHITE_PLAYER = 'white_player';
    public const BLACK_PLAYER = 'black_player';
    public const RESULT = 'result';
    public const OPENING_NAME = 'opening_name';
    public const OPENING_ECO = 'opening_eco';
    public const TOTAL_MOVES = 'total_moves';
    public const USER_COLOR = 'user_color';
    public const IS_ANALYZED = 'is_analyzed';
    public const SHARE_TOKEN = 'share_token';
    public const PLAYED_AT = 'played_at';

    protected $fillable = [
        self::PGN,
        self::USER_ID,
        self::WHITE_PLAYER,
        self::BLACK_PLAYER,
        self::RESULT,
        self::OPENING_NAME,
        self::OPENING_ECO,
        self::TOTAL_MOVES,
        self::USER_COLOR,
        self::IS_ANALYZED,
        self::SHARE_TOKEN,
        self::PLAYED_AT,
    ];

    protected $casts = [
        'is_analyzed' => 'boolean',
        'played_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moves()
    {
        return $this->hasMany(GameMove::class)->orderBy('move_number')->orderBy('color');
    }

    public function trainingSessions()
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function generateShareToken(): string
    {
        $token = Str::random(32);
        $this->update([self::SHARE_TOKEN => $token]);
        return $token;
    }

    public function getId(): int { return $this->getAttribute(self::ID); }
    public function getPgn(): string { return $this->getAttribute(self::PGN); }
    public function getUserId(): int { return $this->getAttribute(self::USER_ID); }
    public function getWhitePlayer(): ?string { return $this->getAttribute(self::WHITE_PLAYER); }
    public function getBlackPlayer(): ?string { return $this->getAttribute(self::BLACK_PLAYER); }
    public function getResult(): string { return $this->getAttribute(self::RESULT); }
    public function getOpeningName(): ?string { return $this->getAttribute(self::OPENING_NAME); }
    public function getOpeningEco(): ?string { return $this->getAttribute(self::OPENING_ECO); }
    public function getTotalMoves(): int { return $this->getAttribute(self::TOTAL_MOVES); }
    public function getUserColor(): string { return $this->getAttribute(self::USER_COLOR); }
    public function getIsAnalyzed(): bool { return $this->getAttribute(self::IS_ANALYZED); }
    public function getShareToken(): ?string { return $this->getAttribute(self::SHARE_TOKEN); }
    public function getPlayedAt(): ?string { return $this->getAttribute(self::PLAYED_AT)?->toDateString(); }
    public function getCreatedAt(): string { return $this->getAttribute('created_at'); }
    public function getDeletedAt(): ?string { return $this->getAttribute('deleted_at'); }
}
