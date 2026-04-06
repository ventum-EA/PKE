<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\GameMove;
use App\Models\User;
use App\Services\GameService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_game_creates_move_records_with_classifications(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);

        $game = Game::factory()->create([
            'user_id' => $user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6',
        ]);

        /** @var GameService $service */
        $service = $this->app->make(GameService::class);
        $result  = $service->analyzeGame($game->id);

        $this->assertSame($game->id, $result['game_id']);
        $this->assertGreaterThan(0, $result['total_moves']);
        $this->assertSame(
            $result['total_moves'],
            GameMove::where('game_id', $game->id)->count()
        );
        $this->assertTrue($game->fresh()->is_analyzed);
    }

    public function test_dashboard_stats_calculates_win_rate_correctly(): void
    {
        $this->seed(RoleAndPermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('user');
        $this->actingAs($user);

        Game::factory()->count(7)->create([
            'user_id' => $user->id, 'result' => '1-0', 'user_color' => 'white',
        ]);
        Game::factory()->count(3)->create([
            'user_id' => $user->id, 'result' => '0-1', 'user_color' => 'white',
        ]);

        /** @var GameService $service */
        $service = $this->app->make(GameService::class);
        $stats   = $service->getDashboardStats();

        $this->assertSame(10, $stats['summary']['total_games']);
        $this->assertSame(7, $stats['summary']['wins']);
        $this->assertSame(3, $stats['summary']['losses']);
        $this->assertSame(70.0, $stats['summary']['win_rate']);
    }
}
