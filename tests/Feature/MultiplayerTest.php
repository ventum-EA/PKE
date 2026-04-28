<?php

namespace Tests\Feature;

use App\Models\OnlineGame;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiplayerTest extends TestCase
{
    use RefreshDatabase;

    protected User $white;
    protected User $black;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->white = User::factory()->create(['elo_rating' => 1200]);
        $this->white->assignRole('user');
        $this->black = User::factory()->create(['elo_rating' => 1200]);
        $this->black->assignRole('user');
    }

    // ── Game creation ─────────────────────────────────────

    public function test_user_can_create_invite_game(): void
    {
        $response = $this->actingAs($this->white)->postJson('/api/multiplayer/create', [
            'color' => 'white', 'time_control' => 600, 'rated' => true,
        ]);
        $response->assertStatus(201);
        $response->assertJsonStructure(['payload' => ['game_id', 'invite_token', 'invite_url']]);
    }

    public function test_user_can_join_by_invite(): void
    {
        $game = OnlineGame::create([
            'white_id' => $this->white->id, 'status' => 'waiting',
            'invite_token' => 'testtoken123', 'time_control' => 600,
            'white_time_remaining' => 600000, 'black_time_remaining' => 600000,
        ]);

        $response = $this->actingAs($this->black)->postJson('/api/multiplayer/join/testtoken123');
        $response->assertStatus(200);
        $this->assertEquals('active', $game->fresh()->status);
        $this->assertEquals($this->black->id, $game->fresh()->black_id);
    }

    public function test_cannot_join_own_game(): void
    {
        OnlineGame::create([
            'white_id' => $this->white->id, 'status' => 'waiting',
            'invite_token' => 'selftoken', 'time_control' => 600,
            'white_time_remaining' => 600000, 'black_time_remaining' => 600000,
        ]);

        $response = $this->actingAs($this->white)->postJson('/api/multiplayer/join/selftoken');
        $response->assertStatus(200); // Returns existing game, doesn't crash
    }

    public function test_invalid_invite_returns_404(): void
    {
        $response = $this->actingAs($this->black)->postJson('/api/multiplayer/join/nonexistent');
        $response->assertStatus(404);
    }

    // ── Matchmaking ───────────────────────────────────────

    public function test_user_can_join_queue(): void
    {
        $response = $this->actingAs($this->white)->postJson('/api/multiplayer/queue/join', ['time_control' => 600]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('payload.in_queue'));
    }

    public function test_user_can_leave_queue(): void
    {
        $this->actingAs($this->white)->postJson('/api/multiplayer/queue/join');
        $response = $this->actingAs($this->white)->postJson('/api/multiplayer/queue/leave');
        $response->assertStatus(200);
        $this->assertFalse($response->json('payload.in_queue'));
    }

    // ── Game actions ──────────────────────────────────────

    public function test_player_can_resign(): void
    {
        $game = $this->createActiveGame();
        $response = $this->actingAs($this->white)->postJson("/api/multiplayer/{$game->id}/resign");
        $response->assertStatus(200);
        $this->assertEquals('completed', $game->fresh()->status);
        $this->assertEquals('0-1', $game->fresh()->result);
    }

    public function test_player_can_offer_draw(): void
    {
        $game = $this->createActiveGame();
        $response = $this->actingAs($this->white)->postJson("/api/multiplayer/{$game->id}/draw", ['action' => 'offer']);
        $response->assertStatus(200);
        $this->assertEquals('white', $game->fresh()->draw_offered_by);
    }

    public function test_opponent_can_accept_draw(): void
    {
        $game = $this->createActiveGame();
        $game->update(['draw_offered_by' => 'white']);
        $response = $this->actingAs($this->black)->postJson("/api/multiplayer/{$game->id}/draw", ['action' => 'accept']);
        $response->assertStatus(200);
        $this->assertEquals('completed', $game->fresh()->status);
        $this->assertEquals('1/2-1/2', $game->fresh()->result);
    }

    public function test_cannot_accept_own_draw_offer(): void
    {
        $game = $this->createActiveGame();
        $game->update(['draw_offered_by' => 'white']);
        $response = $this->actingAs($this->white)->postJson("/api/multiplayer/{$game->id}/draw", ['action' => 'accept']);
        $response->assertStatus(400);
    }

    public function test_non_player_cannot_access_game(): void
    {
        $game = $this->createActiveGame();
        $outsider = User::factory()->create();
        $outsider->assignRole('user');
        $response = $this->actingAs($outsider)->getJson("/api/multiplayer/{$game->id}");
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_multiplayer(): void
    {
        $this->postJson('/api/multiplayer/create')->assertStatus(401);
        $this->postJson('/api/multiplayer/queue/join')->assertStatus(401);
        $this->getJson('/api/multiplayer/history')->assertStatus(401);
    }

    public function test_game_history(): void
    {
        $this->createActiveGame();
        $response = $this->actingAs($this->white)->getJson('/api/multiplayer/history');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload.games'));
    }

    // ── Helpers ───────────────────────────────────────────

    private function createActiveGame(): OnlineGame
    {
        return OnlineGame::create([
            'white_id' => $this->white->id, 'black_id' => $this->black->id,
            'status' => 'active', 'time_control' => 600, 'rated' => true,
            'white_time_remaining' => 600000, 'black_time_remaining' => 600000,
            'white_elo_before' => 1200, 'black_elo_before' => 1200,
            'last_move_at' => now(),
        ]);
    }
}
