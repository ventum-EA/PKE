<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    protected $model = User::class;
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => 'user',
            'elo_rating' => fake()->numberBetween(800, 2200),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => 'admin',
            'elo_rating' => 2000,
        ])->afterCreating(function (User $user) {
            $user->assignRole('admin');
        });
    }
}
