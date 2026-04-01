<?php

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleAndPermissionSeeder::class);
        $this->call(OpeningSeeder::class);
        $this->call(LessonSeeder::class);

        $admin = User::factory()->admin()->create([
            'name' => 'admin',
            'email' => 'admin@example.com',
        ]);

        $player = User::factory()->create([
            'name' => 'speletajs',
            'email' => 'user@example.com',
            'elo_rating' => 1450,
        ]);
        $player->assignRole('user');

        $extraUsers = User::factory()->count(8)->create();
        $extraUsers->each(fn($u) => $u->assignRole('user'));

        $allUsers = User::all();

        Game::factory()
            ->count(50)
            ->recycle($allUsers)
            ->create();

        $this->command->info('Datu bāze veiksmīgi aizpildīta ar 10 lietotājiem un 50 partijām!');
    }
}
