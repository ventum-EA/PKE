<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->getId(),
            'name'            => $this->getName(),
            'email'           => $this->getEmail(),
            'role'            => $this->getRole(),
            'elo_rating'      => $this->getEloRating(),
            'preferred_color' => $this->preferred_color,
            'locale'          => $this->locale,
            'dark_mode'       => (bool) $this->dark_mode,
            'sound_enabled'   => (bool) $this->sound_enabled,
            'font_size'       => $this->font_size ?? 'medium',
            'high_contrast'        => (bool) $this->high_contrast,
            'two_factor_enabled'   => (bool) $this->two_factor_enabled,
            'created_at'           => $this->getCreatedAt(),
        ];
    }
}
