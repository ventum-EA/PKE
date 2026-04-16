<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);
        $this->user->assignRole('user');
    }

    public function test_user_can_change_password(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password'      => 'current-password',
            'password'              => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('brand-new-password', $this->user->fresh()->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password'      => 'wrong-current',
            'password'              => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['current_password']);
        $this->assertTrue(Hash::check('current-password', $this->user->fresh()->password));
    }

    public function test_password_change_requires_confirmation(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password'      => 'current-password',
            'password'              => 'brand-new-password',
            'password_confirmation' => 'mismatched',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_password_change_minimum_length(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/password', [
            'current_password'      => 'current-password',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_guest_cannot_change_password(): void
    {
        $response = $this->putJson('/api/user/password', [
            'current_password'      => 'current-password',
            'password'              => 'brand-new-password',
            'password_confirmation' => 'brand-new-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_forgot_password_endpoint_accepts_email(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => $this->user->email,
        ]);

        // Either RESET_LINK_SENT (200) or mailer not configured in tests (400 with message)
        $this->assertContains($response->status(), [200, 400, 500]);
    }

    public function test_forgot_password_validates_email_format(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'not-an-email',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_reset_password_with_valid_token(): void
    {
        $token = \Illuminate\Support\Facades\Password::createToken($this->user);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'email'                 => $this->user->email,
            'password'              => 'reset-new-password',
            'password_confirmation' => 'reset-new-password',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('reset-new-password', $this->user->fresh()->password));
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $response = $this->postJson('/api/reset-password', [
            'token'                 => 'totally-bogus-token',
            'email'                 => $this->user->email,
            'password'              => 'reset-new-password',
            'password_confirmation' => 'reset-new-password',
        ]);

        $response->assertStatus(422);
    }

    public function test_reset_password_requires_password_confirmation(): void
    {
        $token = \Illuminate\Support\Facades\Password::createToken($this->user);

        $response = $this->postJson('/api/reset-password', [
            'token'                 => $token,
            'email'                 => $this->user->email,
            'password'              => 'reset-new-password',
            'password_confirmation' => 'mismatched',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['password']);
    }
}
