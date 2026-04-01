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
        $user = $this->userRepo->store($userData);
        $user->assignRole($data->role ?? 'user');

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

        $this->userRepo->update($user, $updateData);
        return $user;
    }

    public function updateSettings(array $settings): User
    {
        $user = $this->auth->user();
        $allowed = ['preferred_color', 'locale', 'dark_mode', 'sound_enabled', 'font_size', 'high_contrast'];
        $filtered = array_intersect_key($settings, array_flip($allowed));

        // Validate font_size enum
        if (isset($filtered['font_size']) && !in_array($filtered['font_size'], ['small', 'medium', 'large'], true)) {
            unset($filtered['font_size']);
        }

        $this->userRepo->update($user, $filtered);
        return $user->fresh();
    }
}
