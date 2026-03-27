<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    public function test_user_can_create_game(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/game/create', [
            'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6',
            'white_player' => 'Test White',
            'black_player' => 'Test Black',
            'result' => '1-0',
            'user_color' => 'white',
            'total_moves' => 3,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('games', ['white_player' => 'Test White']);
    }

    public function test_user_can_retrieve_games(): void
    {
        Game::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/games');
        $response->assertStatus(200);
    }

    public function test_user_can_view_single_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/game/{$game->id}");
        $response->assertStatus(200);
    }

    public function test_user_can_delete_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/game/{$game->id}");
        $response->assertStatus(200);
    }

    public function test_user_can_analyze_game(): void
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn' => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/game/{$game->id}/analyze");
        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['game_id', 'total_moves', 'errors_count']]);
    }

    public function test_guest_cannot_access_games(): void
    {
        $response = $this->getJson('/api/games');
        $response->assertStatus(401);
    }

    public function test_user_can_share_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/game/{$game->id}/share");
        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['share_url']]);
    }
}
