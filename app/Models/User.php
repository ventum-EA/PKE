<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    public const NAME = 'name';
    public const EMAIL = 'email';
    public const ID = 'id';
    public const PASSWORD = 'password';
    public const ROLE = 'role';
    public const ELO_RATING = 'elo_rating';
    public const PREFERRED_COLOR = 'preferred_color';
    public const LOCALE = 'locale';
    public const DARK_MODE = 'dark_mode';
    public const SOUND_ENABLED = 'sound_enabled';

    protected $guard_name = 'sanctum';

    protected $fillable = [
        self::NAME, self::EMAIL, self::PASSWORD, self::ROLE,
        self::ELO_RATING, self::PREFERRED_COLOR, self::LOCALE,
        self::DARK_MODE, self::SOUND_ENABLED,
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dark_mode' => 'boolean',
            'sound_enabled' => 'boolean',
        ];
    }

    public function games() { return $this->hasMany(Game::class); }
    public function trainingSessions() { return $this->hasMany(TrainingSession::class); }

    public function getId(): int { return $this->getAttribute(self::ID); }
    public function getName(): string { return $this->getAttribute(self::NAME); }
    public function getEmail(): string { return $this->getAttribute(self::EMAIL); }
    public function getRole(): string { return $this->getAttribute(self::ROLE); }
    public function getEloRating(): int { return $this->getAttribute(self::ELO_RATING); }
    public function getCreatedAt(): string { return $this->getAttribute('created_at'); }
    public function getUpdatedAt(): string { return $this->getAttribute('updated_at'); }
}
