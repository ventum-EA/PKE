<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameMove;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create([
            'password' => Hash::make('my-password'),
        ]);
        $this->user->assignRole('user');
    }

    public function test_user_can_delete_own_account(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/user/me', [
            'password' => 'my-password',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    public function test_account_deletion_requires_password_confirmation(): void
    {
        $response = $this->actingAs($this->user)->deleteJson('/api/user/me', [
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }

    public function test_account_deletion_cascades_to_related_data(): void
    {
        $game = Game::factory()->create(['user_id' => $this->user->id]);
        GameMove::create([
            'game_id'     => $game->id,
            'move_number' => 1,
            'color'       => 'white',
            'move_san'    => 'e4',
        ]);
        TrainingSession::create([
            'user_id'      => $this->user->id,
            'fen'          => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            'correct_move' => 'e2e4',
            'category'     => 'tactical',
        ]);

        $this->actingAs($this->user)->deleteJson('/api/user/me', [
            'password' => 'my-password',
        ])->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
        $this->assertDatabaseMissing('games', ['id' => $game->id]);
        $this->assertDatabaseMissing('game_moves', ['game_id' => $game->id]);
        $this->assertDatabaseMissing('training_sessions', ['user_id' => $this->user->id]);
    }

    public function test_guest_cannot_delete_any_account(): void
    {
        $response = $this->deleteJson('/api/user/me', ['password' => 'anything']);
        $response->assertStatus(401);
    }
}
