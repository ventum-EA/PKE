<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->admin->assignRole('admin');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('user');
    }

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(3)->create()->each(fn($u) => $u->assignRole('user'));

        $response = $this->actingAs($this->admin)->getJson('/api/users');
        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['users' => ['data', 'meta']]]);
    }

    public function test_admin_can_view_a_user(): void
    {
        $response = $this->actingAs($this->admin)->getJson("/api/user/{$this->regularUser->id}");
        $response->assertStatus(200);
        $response->assertJsonPath('payload.user.id', $this->regularUser->id);
    }

    public function test_admin_can_create_user(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/user/create', [
            'name'     => 'createdbyadmin',
            'email'    => 'created@example.com',
            'password' => 'admin-set-pwd',
            'role'     => 'user',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'created@example.com']);
    }

    public function test_admin_can_delete_user(): void
    {
        $target = User::factory()->create();
        $target->assignRole('user');

        $response = $this->actingAs($this->admin)->deleteJson("/api/user/{$target->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_regular_user_cannot_list_users(): void
    {
        $response = $this->actingAs($this->regularUser)->getJson('/api/users');
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_create_user(): void
    {
        $response = $this->actingAs($this->regularUser)->postJson('/api/user/create', [
            'name'     => 'sneaky',
            'email'    => 'sneaky@example.com',
            'password' => 'whatever',
        ]);
        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_delete_other_users(): void
    {
        $target = User::factory()->create();
        $response = $this->actingAs($this->regularUser)->deleteJson("/api/user/{$target->id}");
        $response->assertStatus(403);
    }

    public function test_guest_cannot_access_user_management(): void
    {
        $this->getJson('/api/users')->assertStatus(401);
    }
}
