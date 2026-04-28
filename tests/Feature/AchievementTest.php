<?php

namespace Tests\Feature;

use App\Models\Achievement;
use App\Models\User;
use Database\Seeders\AchievementSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->seed(AchievementSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    public function test_guest_cannot_access_achievements(): void
    {
        $this->getJson('/api/achievements')->assertStatus(401);
    }

    public function test_user_can_list_achievements(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/achievements');
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'payload' => ['achievements']]);
        $this->assertGreaterThanOrEqual(20, count($response->json('payload.achievements')));
    }

    public function test_achievements_include_user_progress(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/achievements');
        $first = $response->json('payload.achievements.0');
        $this->assertArrayHasKey('user_progress', $first);
        $this->assertArrayHasKey('user_unlocked', $first);
    }

    public function test_check_returns_newly_unlocked(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/achievements/check');
        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'payload' => ['newly_unlocked']]);
    }

    public function test_check_does_not_unlock_without_progress(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/achievements/check');
        $this->assertEmpty($response->json('payload.newly_unlocked'));
    }
}
