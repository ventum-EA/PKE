<?php

namespace Tests\Feature;

use App\Jobs\AnalyzeGameJob;
use App\Models\Game;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ServerAnalysisJobTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    public function test_server_analysis_dispatches_queue_job(): void
    {
        Queue::fake();

        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6 3. Bb5 a6',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/game/{$game->id}/analyze", ['server' => true, 'depth' => 20]);

        $response->assertStatus(200);
        $response->assertJsonPath('payload.queued', true);
        $response->assertJsonPath('payload.game_id', $game->id);

        Queue::assertPushed(AnalyzeGameJob::class, function (AnalyzeGameJob $job) use ($game) {
            return $job->gameId === $game->id && $job->depth === 20;
        });
    }

    public function test_client_side_analysis_does_not_dispatch_job(): void
    {
        Queue::fake();

        $game = Game::factory()->create([
            'user_id' => $this->user->id,
            'pgn'     => '1. e4 e5 2. Nf3 Nc6',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/game/{$game->id}/analyze");

        $response->assertStatus(200);
        Queue::assertNothingPushed();
    }

    public function test_server_analysis_uses_default_depth_when_not_specified(): void
    {
        Queue::fake();

        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson("/api/game/{$game->id}/analyze", ['server' => true]);

        Queue::assertPushed(AnalyzeGameJob::class, function (AnalyzeGameJob $job) {
            // Default depth from controller is 15
            return $job->depth === 15;
        });
    }

    public function test_guest_cannot_dispatch_server_analysis(): void
    {
        Queue::fake();

        $game = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/game/{$game->id}/analyze", ['server' => true]);
        $response->assertStatus(401);
        Queue::assertNothingPushed();
    }
}
