<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'role' => $this->getRoleNames()->first() ?? 'user',
            'elo_rating' => $this->getEloRating(),
            'preferred_color' => $this->preferred_color,
            'locale' => $this->locale,
            'dark_mode' => (bool) $this->dark_mode,
            'sound_enabled' => (bool) ($this->sound_enabled ?? true),
            'font_size' => $this->font_size ?? 'medium',
            'high_contrast' => (bool) $this->high_contrast,
            'two_factor_enabled' => (bool) $this->two_factor_enabled,
            'board_coordinates' => (bool) ($this->board_coordinates ?? true),
            'move_confirmation' => (bool) ($this->move_confirmation ?? false),
            'auto_queen' => (bool) ($this->auto_queen ?? true),
            'default_difficulty' => (int) ($this->default_difficulty ?? 5),
            'show_elo_opponent' => (bool) ($this->show_elo_opponent ?? true),
            'animation_speed' => $this->animation_speed ?? 'normal',
            'board_theme' => $this->board_theme ?? 'classic',
            'piece_style' => $this->piece_style ?? 'standard',
            'email_friend_requests' => (bool) ($this->email_friend_requests ?? true),
            'email_game_invites' => (bool) ($this->email_game_invites ?? true),
            'email_weekly_digest' => (bool) ($this->email_weekly_digest ?? true),
            'created_at' => $this->getCreatedAt(),
        ];
    }
}
