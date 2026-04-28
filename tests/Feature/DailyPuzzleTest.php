<?php

namespace Tests\Feature;

use App\Models\DailyPuzzle;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyPuzzleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        DailyPuzzle::create([
            'puzzle_date'    => Carbon::today()->toDateString(),
            'fen'            => '6k1/5ppp/8/8/8/8/5PPP/4R1K1 w - - 0 1',
            'correct_move'   => 'e1e8',
            'theme'          => 'Back Rank Mate',
            'theme_lv'       => 'Pēdējās rindas mats',
            'explanation'    => 'Re8# is checkmate.',
            'explanation_lv' => 'Te8# ir mats.',
            'difficulty'     => 1,
        ]);
    }

    public function test_guest_cannot_access_daily_puzzle(): void
    {
        $this->getJson('/api/daily-puzzle')->assertStatus(401);
    }

    public function test_user_can_get_todays_puzzle(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/daily-puzzle');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'message',
            'payload' => ['puzzle' => ['id', 'fen', 'difficulty', 'theme'], 'leaderboard'],
        ]);
    }

    public function test_correct_move_hidden_before_solving(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/daily-puzzle');
        $this->assertNull($response->json('payload.puzzle.correct_move'));
    }

    public function test_incorrect_submission(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/daily-puzzle/submit', [
            'move' => 'e1e2',
            'time_spent' => 5,
        ]);
        $response->assertStatus(200);
        $this->assertFalse($response->json('payload.solved'));
        $this->assertNull($response->json('payload.correct_move'));
    }

    public function test_correct_submission(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/daily-puzzle/submit', [
            'move' => 'e1e8',
            'time_spent' => 10,
        ]);
        $response->assertStatus(200);
        $this->assertTrue($response->json('payload.solved'));
        $this->assertEquals('e1e8', $response->json('payload.correct_move'));
    }

    public function test_cannot_solve_twice(): void
    {
        $this->actingAs($this->user)->postJson('/api/daily-puzzle/submit', [
            'move' => 'e1e8', 'time_spent' => 5,
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/daily-puzzle/submit', [
            'move' => 'e1e8', 'time_spent' => 5,
        ]);
        $this->assertTrue($response->json('payload.already'));
    }

    public function test_history_endpoint(): void
    {
        $this->actingAs($this->user)->postJson('/api/daily-puzzle/submit', [
            'move' => 'e1e8', 'time_spent' => 8,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/daily-puzzle/history');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('payload.history'));
    }

    public function test_404_when_no_puzzle_today(): void
    {
        DailyPuzzle::query()->delete();
        $response = $this->actingAs($this->user)->getJson('/api/daily-puzzle');
        $response->assertStatus(404);
    }
}
