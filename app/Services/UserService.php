<?php

namespace App\Services;

use App\Data\UserData;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        protected UserRepository $userRepo,
        protected Guard $auth
    ) {}

    public function createUser(UserData $data): array
    {
        $userData = $data->toArray();
        $userData['password'] = Hash::make($data->password);
        $role = $userData['role'] ?? 'user';
        unset($userData['role']);  // ← strip; not a column anymore
        $user = $this->userRepo->store($userData);
        $user->assignRole($role);
        return ['user' => $user];
    }

    public function updateExistingUser(UserData $data): User
    {
        $user = $this->userRepo->findById($data->user_id);
        $updateData = $data->toArray();

        if (!empty($data->password)) {
            $updateData['password'] = Hash::make($data->password);
        } else {
            unset($updateData['password']);
        }

        // Handle role separately via Spatie if present
        if (!empty($updateData['role'])) {
            $user->syncRoles([$updateData['role']]);
        }
        unset($updateData['role']);  // ← strip from column update

        $this->userRepo->update($user, $updateData);
        return $user;
    }

    public function updateSettings(array $settings): User
    {
        $user = $this->auth->user();
        $allowed = [
            'preferred_color', 'locale', 'dark_mode', 'sound_enabled', 'font_size', 'high_contrast',
            'board_coordinates', 'move_confirmation', 'auto_queen', 'default_difficulty',
            'show_elo_opponent', 'animation_speed', 'board_theme', 'piece_style',
            'email_friend_requests', 'email_game_invites', 'email_weekly_digest',
        ];
        $filtered = array_intersect_key($settings, array_flip($allowed));

        // Validate font_size enum
        if (isset($filtered['font_size']) && !in_array($filtered['font_size'], ['small', 'medium', 'large'], true)) {
            unset($filtered['font_size']);
        }

        // Validate animation_speed enum
        if (isset($filtered['animation_speed']) && !in_array($filtered['animation_speed'], ['slow', 'normal', 'fast', 'none'], true)) {
            unset($filtered['animation_speed']);
        }

        // Validate board_theme
        if (isset($filtered['board_theme']) && !in_array($filtered['board_theme'], ['classic', 'brown', 'blue', 'green', 'purple', 'high_contrast'], true)) {
            unset($filtered['board_theme']);
        }

        // Validate piece_style
        if (isset($filtered['piece_style']) && !in_array($filtered['piece_style'], ['standard', 'neo', 'alpha', 'medieval'], true)) {
            unset($filtered['piece_style']);
        }

        // Validate default_difficulty range
        if (isset($filtered['default_difficulty'])) {
            $filtered['default_difficulty'] = max(0, min(20, (int) $filtered['default_difficulty']));
        }

        $this->userRepo->update($user, $filtered);
        return $user->fresh();
    }
}
