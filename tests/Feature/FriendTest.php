<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FriendTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $friend;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create(['name' => 'Alice']);
        $this->user->assignRole('user');
        $this->friend = User::factory()->create(['name' => 'Bob']);
        $this->friend->assignRole('user');
    }

    public function test_user_can_send_friend_request(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/friends/add', ['name' => 'Bob']);
        $response->assertStatus(201);
        $this->assertDatabaseHas('friendships', [
            'user_id' => $this->user->id, 'friend_id' => $this->friend->id, 'status' => 'pending',
        ]);
    }

    public function test_cannot_add_self(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/friends/add', ['name' => 'Alice']);
        $response->assertStatus(400);
    }

    public function test_cannot_add_nonexistent_user(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/friends/add', ['name' => 'Nobody']);
        $response->assertStatus(404);
    }

    public function test_cannot_add_existing_friend(): void
    {
        $this->actingAs($this->user)->postJson('/api/friends/add', ['name' => 'Bob']);
        $response = $this->actingAs($this->user)->postJson('/api/friends/add', ['name' => 'Bob']);
        $response->assertStatus(409);
    }

    public function test_friend_can_accept_request(): void
    {
        $this->actingAs($this->user)->postJson('/api/friends/add', ['name' => 'Bob']);
        $friendship = Friendship::first();

        $response = $this->actingAs($this->friend)->postJson("/api/friends/{$friendship->id}/accept");
        $response->assertStatus(200);
        $this->assertEquals('accepted', $friendship->fresh()->status);
    }

    public function test_user_can_remove_friend(): void
    {
        Friendship::create(['user_id' => $this->user->id, 'friend_id' => $this->friend->id, 'status' => 'accepted']);

        $response = $this->actingAs($this->user)->deleteJson("/api/friends/{$this->friend->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('friendships', ['user_id' => $this->user->id, 'friend_id' => $this->friend->id]);
    }

    public function test_friends_list_shows_accepted(): void
    {
        Friendship::create(['user_id' => $this->user->id, 'friend_id' => $this->friend->id, 'status' => 'accepted']);

        $response = $this->actingAs($this->user)->getJson('/api/friends');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload.friends'));
    }

    public function test_incoming_requests_shown(): void
    {
        Friendship::create(['user_id' => $this->friend->id, 'friend_id' => $this->user->id, 'status' => 'pending']);

        $response = $this->actingAs($this->user)->getJson('/api/friends');
        $this->assertCount(1, $response->json('payload.incoming'));
    }
}
