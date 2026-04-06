<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'newplayer',
            'email'                 => 'newplayer@example.com',
            'password'              => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'user' => ['id', 'name', 'email']]);
        $this->assertDatabaseHas('users', ['email' => 'newplayer@example.com', 'name' => 'newplayer']);
    }

    public function test_register_requires_unique_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->postJson('/api/register', [
            'name'                  => 'somebody',
            'email'                 => 'taken@example.com',
            'password'              => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'mismatch',
            'email'                 => 'mm@example.com',
            'password'              => 'secret-password',
            'password_confirmation' => 'something-else',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_register_password_must_be_at_least_8_chars(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'shortpw',
            'email'                 => 'sp@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'login@example.com',
            'password' => Hash::make('mypassword'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'login@example.com',
            'password' => 'mypassword',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'user' => ['id', 'email']]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email'    => 'wrong@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email'    => 'wrong@example.com',
            'password' => 'incorrect-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->postJson('/api/login', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->postJson('/api/logout');
        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)->getJson('/api/user');
        $response->assertStatus(200);
        $response->assertJsonPath('payload.user.email', $user->email);
    }

    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }
}
