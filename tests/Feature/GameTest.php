<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameMove;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    // ----------------- Create -----------------

    public function test_user_can_create_game_from_pgn(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/game/create', [
            'pgn'          => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6',
            'white_player' => 'Magnus',
            'black_player' => 'Hikaru',
            'result'       => '1-0',
            'user_color'   => 'white',
            'total_moves'  => 3,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('payload.game.white_player', 'Magnus');
        $this->assertDatabaseHas('games', [
            'user_id'      => $this->user->id,
            'white_player' => 'Magnus',
            'black_player' => 'Hikaru',
            'result'       => '1-0',
        ]);
    }

    public function test_create_game_requires_pgn(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/game/create', [
            'white_player' => 'noPGN',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['pgn']);
    }

    public function test_create_game_validates_result_enum(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/game/create', [
            'pgn'    => '1. e4',
            'result' => 'bogus',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['result']);
    }

    public function test_create_game_validates_user_color(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/game/create', [
            'pgn'        => '1. e4',
            'user_color' => 'green',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_color']);
    }

    // ----------------- Read -----------------

    public function test_user_can_list_games(): void
    {
        Game::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/games');
        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['games' => ['data', 'meta']]]);
    }

    public function test_games_list_paginates(): void
    {
        Game::factory()->count(20)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/api/games?perPage=5');
        $response->assertStatus(200);
        $this->assertCount(5, $response->json('payload.games.data'));
    }

    public function test_user_can_view_a_single_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson("/api/game/{$game->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('payload.game.id', $game->id);
    }

    public function test_view_unknown_game_returns_404(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/game/999999');
        $response->assertStatus(404);
    }

    // ----------------- Delete -----------------

    public function test_user_can_delete_their_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->deleteJson("/api/game/{$game->id}");
        $response->assertStatus(200);
        $this->assertSoftDeleted('games', ['id' => $game->id]);
    }

    // ----------------- Analyze -----------------

    public function test_user_can_analyze_game(): void
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/game/{$game->id}/analyze");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payload' => ['game_id', 'total_moves', 'errors_count', 'blunders', 'mistakes', 'inaccuracies'],
        ]);

        $this->assertTrue($game->fresh()->is_analyzed);
        $this->assertGreaterThan(0, GameMove::where('game_id', $game->id)->count());
    }

    public function test_analyze_classifies_moves_into_known_categories(): void
    {
        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5',
        ]);

        $this->actingAs($this->user)->postJson("/api/game/{$game->id}/analyze")->assertStatus(200);

        $validClassifications = ['best', 'excellent', 'good', 'inaccuracy', 'mistake', 'blunder'];
        $moves                = GameMove::where('game_id', $game->id)->get();

        foreach ($moves as $move) {
            $this->assertContains($move->classification, $validClassifications);
        }
    }

    public function test_save_moves_endpoint_persists_client_analysis(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/game/{$game->id}/moves", [
            'moves' => [
                [
                    'move_number'    => 1,
                    'color'          => 'white',
                    'move_san'       => 'e4',
                    'eval_before'    => 0.2,
                    'eval_after'     => 0.3,
                    'eval_diff'      => 0.1,
                    'best_move'      => 'e2e4',
                    'classification' => 'best',
                ],
                [
                    'move_number'    => 1,
                    'color'          => 'black',
                    'move_san'       => 'e5',
                    'eval_before'    => 0.3,
                    'eval_after'     => 0.2,
                    'eval_diff'      => 0.1,
                    'classification' => 'excellent',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseCount('game_moves', 2);
        $this->assertTrue($game->fresh()->is_analyzed);
    }

    public function test_save_moves_replaces_previous_analysis(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);
        GameMove::create([
            'game_id'     => $game->id,
            'move_number' => 99,
            'color'       => 'white',
            'move_san'    => 'old',
        ]);

        $this->actingAs($this->user)->postJson("/api/game/{$game->id}/moves", [
            'moves' => [
                ['move_number' => 1, 'color' => 'white', 'move_san' => 'e4'],
            ],
        ])->assertStatus(200);

        $this->assertSame(1, GameMove::where('game_id', $game->id)->count());
        $this->assertSame('e4', GameMove::where('game_id', $game->id)->first()->move_san);
    }

    public function test_save_moves_validates_required_fields(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/game/{$game->id}/moves", [
            'moves' => [['color' => 'white']], // missing move_number, move_san
        ]);
        $response->assertStatus(422);
    }

    public function test_get_moves_returns_persisted_analysis(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);
        GameMove::create([
            'game_id' => $game->id, 'move_number' => 1, 'color' => 'white', 'move_san' => 'e4',
        ]);
        GameMove::create([
            'game_id' => $game->id, 'move_number' => 1, 'color' => 'black', 'move_san' => 'e5',
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/game/{$game->id}/moves");
        $response->assertStatus(200);
        $this->assertCount(2, $response->json('payload.moves'));
    }

    // ----------------- Share & download -----------------

    public function test_user_can_share_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/game/{$game->id}/share");
        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['share_url']]);
        $this->assertNotNull($game->fresh()->share_token);
    }

    public function test_shared_game_is_publicly_viewable(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);
        $token = $game->generateShareToken();

        // No actingAs — guest access via the public route
        $response = $this->getJson("/api/shared/{$token}");
        $response->assertStatus(200);
        $response->assertJsonPath('payload.game.id', $game->id);
    }

    public function test_user_can_download_game_as_pgn(): void
    {
        $game = Game::factory()->create([
            'user_id'      => $this->user->id,
            'pgn'          => '1. e4 e5 2. Nf3',
            'white_player' => 'Alice',
            'black_player' => 'Bob',
            'result'       => '1-0',
        ]);

        $response = $this->actingAs($this->user)->get("/api/game/{$game->id}/download");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/x-chess-pgn');
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('.pgn', $disposition);

        $body = $response->getContent();
        $this->assertStringContainsString('Alice', $body);
        $this->assertStringContainsString('Bob', $body);
        $this->assertStringContainsString('e4', $body);
    }

    // ----------------- Stats -----------------

    public function test_stats_endpoint_returns_summary_for_user(): void
    {
        Game::factory()->count(3)->create([
            'user_id' => $this->user->id, 'result' => '1-0', 'user_color' => 'white',
        ]);
        Game::factory()->count(2)->create([
            'user_id' => $this->user->id, 'result' => '0-1', 'user_color' => 'white',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/games/stats');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payload' => ['summary' => ['total_games', 'wins', 'losses', 'draws', 'win_rate']],
        ]);
        $this->assertSame(5, $response->json('payload.summary.total_games'));
        $this->assertSame(3, $response->json('payload.summary.wins'));
        $this->assertSame(2, $response->json('payload.summary.losses'));
    }

    // ----------------- Auth gating -----------------

    public function test_guest_cannot_list_games(): void
    {
        $this->getJson('/api/games')->assertStatus(401);
    }

    public function test_guest_cannot_create_game(): void
    {
        $this->postJson('/api/game/create', ['pgn' => '1. e4'])->assertStatus(401);
    }

    public function test_guest_cannot_download_game(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);
        $this->getJson("/api/game/{$game->id}/download")->assertStatus(401);
    }
}
