<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates user preference/settings updates.
 *
 * Used by UserController::updateSettings()
 */
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'preferred_color' => 'sometimes|nullable|in:white,black',
            'locale' => 'sometimes|nullable|in:lv,en',
            'dark_mode' => 'sometimes|boolean',
            'sound_enabled' => 'sometimes|boolean',
            'font_size' => 'sometimes|nullable|in:small,medium,large',
            'high_contrast' => 'sometimes|boolean',
            'board_coordinates' => 'sometimes|boolean',
            'move_confirmation' => 'sometimes|boolean',
            'auto_queen' => 'sometimes|boolean',
            'default_difficulty' => 'sometimes|integer|min:0|max:20',
            'show_elo_opponent' => 'sometimes|boolean',
            'animation_speed' => 'sometimes|nullable|in:slow,normal,fast,none',
            'board_theme' => 'sometimes|nullable|in:classic,brown,blue,green,purple,high_contrast',
            'piece_style' => 'sometimes|nullable|in:standard,neo,alpha,medieval',
            'email_friend_requests' => 'sometimes|boolean',
            'email_game_invites' => 'sometimes|boolean',
            'email_weekly_digest' => 'sometimes|boolean',
        ];
    }
}
