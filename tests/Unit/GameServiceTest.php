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

    private User $user;
    private GameService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->actingAs($this->user);
        $this->service = $this->app->make(GameService::class);
    }

    public function test_analyze_game_returns_not_analyzed_when_no_moves_exist(): void
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6',
        ]);

        $result = $this->service->analyzeGame($game->id);

        $this->assertSame($game->id, $result['game_id']);
        $this->assertFalse($result['analyzed']);
        $this->assertArrayHasKey('message', $result);
    }

    public function test_analyze_game_returns_stats_when_moves_exist(): void
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6',
        ]);

        // Simulate the frontend saving WASM analysis results
        $now = now();
        GameMove::insert([
            [
                'game_id' => $game->id, 'move_number' => 1, 'color' => 'white',
                'move_san' => 'e4', 'classification' => 'best', 'eval_diff' => 0.05,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'game_id' => $game->id, 'move_number' => 1, 'color' => 'black',
                'move_san' => 'e5', 'classification' => 'best', 'eval_diff' => 0.03,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'game_id' => $game->id, 'move_number' => 2, 'color' => 'white',
                'move_san' => 'Nf3', 'classification' => 'inaccuracy', 'eval_diff' => 0.6,
                'error_category' => 'opening',
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'game_id' => $game->id, 'move_number' => 2, 'color' => 'black',
                'move_san' => 'Nc6', 'classification' => 'blunder', 'eval_diff' => 3.2,
                'error_category' => 'tactical',
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $result = $this->service->analyzeGame($game->id);

        $this->assertSame($game->id, $result['game_id']);
        $this->assertTrue($result['analyzed']);
        $this->assertSame(4, $result['total_moves']);
        $this->assertSame(2, $result['errors_count']);
        $this->assertSame(1, $result['blunders']);
        $this->assertSame(0, $result['mistakes']);
        $this->assertSame(1, $result['inaccuracies']);
        $this->assertNotNull($result['avg_eval_loss']);
    }

    public function test_dashboard_stats_calculates_win_rate_correctly(): void
    {
        Game::factory()->count(7)->create([
            'user_id' => $this->user->id, 'result' => '1-0', 'user_color' => 'white',
        ]);
        Game::factory()->count(3)->create([
            'user_id' => $this->user->id, 'result' => '0-1', 'user_color' => 'white',
        ]);

        $stats = $this->service->getDashboardStats();

        $this->assertSame(10, $stats['summary']['total_games']);
        $this->assertSame(7, $stats['summary']['wins']);
        $this->assertSame(3, $stats['summary']['losses']);
        $this->assertSame(70.0, $stats['summary']['win_rate']);
    }
}
