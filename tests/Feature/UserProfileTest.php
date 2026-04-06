<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create([
            'name'  => 'oldname',
            'email' => 'old@example.com',
        ]);
        $this->user->assignRole('user');
    }

    public function test_user_can_update_name_and_email(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/profile', [
            'name'  => 'newname',
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', [
            'id'    => $this->user->id,
            'name'  => 'newname',
            'email' => 'new@example.com',
        ]);
    }

    public function test_profile_update_validates_email_format(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/profile', [
            'name'  => 'fine',
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_rejects_email_used_by_someone_else(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($this->user)->putJson('/api/user/profile', [
            'name'  => 'fine',
            'email' => 'taken@example.com',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_profile_update_allows_keeping_same_email(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/profile', [
            'name'  => 'newname',
            'email' => 'old@example.com',
        ]);

        $response->assertStatus(200);
    }

    public function test_user_can_update_settings(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/settings', [
            'preferred_color' => 'black',
            'sound_enabled'   => false,
            'dark_mode'       => true,
        ]);

        $response->assertStatus(200);
        $fresh = $this->user->fresh();
        $this->assertSame('black', $fresh->preferred_color);
        $this->assertFalse((bool) $fresh->sound_enabled);
        $this->assertTrue((bool) $fresh->dark_mode);
    }

    public function test_user_can_update_accessibility_settings(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/settings', [
            'font_size'     => 'large',
            'high_contrast' => true,
        ]);

        $response->assertStatus(200);
        $fresh = $this->user->fresh();
        $this->assertSame('large', $fresh->font_size);
        $this->assertTrue((bool) $fresh->high_contrast);
    }

    public function test_font_size_rejects_invalid_value(): void
    {
        $this->user->forceFill(['font_size' => 'medium'])->save();

        $this->actingAs($this->user)->putJson('/api/user/settings', [
            'font_size' => 'enormous',
        ])->assertStatus(200);

        // Invalid value silently ignored; stored value unchanged
        $this->assertSame('medium', $this->user->fresh()->font_size);
    }

    public function test_settings_endpoint_ignores_unknown_fields(): void
    {
        $response = $this->actingAs($this->user)->putJson('/api/user/settings', [
            'preferred_color' => 'black',
            'role'            => 'admin', // should be ignored — only allowed keys whitelisted
        ]);

        $response->assertStatus(200);
        $this->assertNotSame('admin', $this->user->fresh()->role);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $response = $this->putJson('/api/user/profile', [
            'name'  => 'x',
            'email' => 'x@example.com',
        ]);
        $response->assertStatus(401);
    }
}
