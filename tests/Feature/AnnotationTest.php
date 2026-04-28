<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameAnnotation;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnotationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $other;
    protected Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
        $this->other = User::factory()->create();
        $this->other->assignRole('user');
        $this->game = Game::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_guest_cannot_access_annotations(): void
    {
        $this->getJson("/api/game/{$this->game->id}/annotations")->assertStatus(401);
    }

    public function test_user_can_list_own_annotations(): void
    {
        GameAnnotation::create([
            'game_id' => $this->game->id, 'user_id' => $this->user->id,
            'move_index' => 0, 'comment' => 'Test',
        ]);
        $response = $this->actingAs($this->user)->getJson("/api/game/{$this->game->id}/annotations");
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload.annotations'));
    }

    public function test_user_can_save_annotation(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/game/{$this->game->id}/annotations", [
            'move_index' => 3,
            'comment' => 'Interesting move',
            'arrows' => [['from' => 'e2', 'to' => 'e4', 'color' => 'green']],
            'highlights' => [['square' => 'e4', 'color' => 'yellow']],
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('game_annotations', [
            'game_id' => $this->game->id, 'move_index' => 3, 'comment' => 'Interesting move',
        ]);
    }

    public function test_user_can_update_annotation(): void
    {
        $this->actingAs($this->user)->postJson("/api/game/{$this->game->id}/annotations", [
            'move_index' => 2, 'comment' => 'First version',
        ]);
        $this->actingAs($this->user)->postJson("/api/game/{$this->game->id}/annotations", [
            'move_index' => 2, 'comment' => 'Updated version',
        ]);
        $this->assertDatabaseHas('game_annotations', ['comment' => 'Updated version']);
        $this->assertDatabaseMissing('game_annotations', ['comment' => 'First version']);
    }

    public function test_user_can_delete_annotation(): void
    {
        $this->actingAs($this->user)->postJson("/api/game/{$this->game->id}/annotations", [
            'move_index' => 5, 'comment' => 'To delete',
        ]);
        $response = $this->actingAs($this->user)->deleteJson("/api/game/{$this->game->id}/annotations/5");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('game_annotations', ['move_index' => 5]);
    }

    public function test_other_user_cannot_see_annotations(): void
    {
        GameAnnotation::create([
            'game_id' => $this->game->id, 'user_id' => $this->user->id,
            'move_index' => 0, 'comment' => 'Private note',
        ]);
        $response = $this->actingAs($this->other)->getJson("/api/game/{$this->game->id}/annotations");
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('payload.annotations'));
    }

    public function test_comment_max_length_validated(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/game/{$this->game->id}/annotations", [
            'move_index' => 1, 'comment' => str_repeat('x', 1001),
        ]);
        $response->assertStatus(422);
    }
}
