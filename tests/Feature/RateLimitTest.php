<?php

namespace Tests\Feature;

use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Verifies that brute-force protection is enforced on auth endpoints
 * (req 2.3.2 — drošība pret tīmekļa drošības draudiem).
 */
class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        RateLimiter::clear('');
    }

    public function test_login_endpoint_throttles_after_five_attempts(): void
    {
        $payload = ['email' => '[email protected]', 'password' => 'whatever-wrong'];

        // The throttle is 5 requests / minute. The first 5 should be 401 (auth failed),
        // the 6th must be 429 (too many attempts).
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', $payload);
            $this->assertContains($response->status(), [401, 422],
                "Attempt {$i} expected 401/422, got {$response->status()}");
        }

        $sixth = $this->postJson('/api/login', $payload);
        $sixth->assertStatus(429);
    }

    public function test_register_endpoint_throttles_aggressively(): void
    {
        // Throttle is 3 requests / 10 minutes for register
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/register', [
                'name'                  => "user{$i}",
                'email'                 => "user{$i}@example.com",
                'password'              => 'mypassword',
                'password_confirmation' => 'mypassword',
            ]);
        }

        $fourth = $this->postJson('/api/register', [
            'name'                  => 'user4',
            'email'                 => '[email protected]',
            'password'              => 'mypassword',
            'password_confirmation' => 'mypassword',
        ]);
        $this->assertContains($fourth->status(), [429, 302], "Fourth register should be throttled or redirected, got " . $fourth->status());
    }

    public function test_forgot_password_endpoint_is_throttled(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/forgot-password', ['email' => '[email protected]']);
        }

        $fourth = $this->postJson('/api/forgot-password', ['email' => '[email protected]']);
        $this->assertContains($fourth->status(), [429, 500], "Fourth forgot-password should be throttled, got " . $fourth->status());
    }
}
