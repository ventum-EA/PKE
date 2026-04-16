<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\GameMove;
use App\Models\TrainingSession;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Game $game;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndPermissionSeeder::class);
        $this->user = User::factory()->create();
        $this->user->assignRole('user');

        $this->game = Game::factory()->create(['user_id' => $this->user->id]);

        // Seed three "error" moves with valid FENs and best moves so the
        // training service will turn them into puzzle sessions.
        $this->seedErrorMove('blunder', 'tactical', 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3', 'e2e4');
        $this->seedErrorMove('mistake', 'positional', 'rnbqkbnr/pp1ppppp/8/2p5/4P3/8/PPPP1PPP/RNBQKBNR w KQkq c6 0 2', 'g1f3');
        $this->seedErrorMove('inaccuracy', 'opening', 'r1bqkbnr/pppp1ppp/2n5/4p3/2B1P3/5N2/PPPP1PPP/RNBQK2R b KQkq - 3 3', 'g8f6');
    }

    private function seedErrorMove(string $classification, string $category, string $fen, string $bestUci): void
    {
        GameMove::create([
            'game_id'        => $this->game->id,
            'move_number'    => mt_rand(5, 20),
            'color'          => 'white',
            'move_san'       => 'h3',
            'fen_before'     => $fen,
            'eval_diff'      => 1.5,
            'best_move'      => $bestUci,
            'classification' => $classification,
            'error_category' => $category,
            'explanation'    => 'Better was the central pawn break.',
        ]);
    }

    public function test_user_can_generate_puzzles_from_game_errors(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/training/generate/{$this->game->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['puzzles']]);
        $puzzles = $response->json('payload.puzzles');
        $this->assertCount(3, $puzzles);

        foreach ($puzzles as $p) {
            $this->assertArrayHasKey('id', $p);
            $this->assertArrayHasKey('fen', $p);
            $this->assertArrayHasKey('category', $p);
        }
    }

    public function test_generated_puzzles_are_categorized_by_mistake_type(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/training/generate/{$this->game->id}");
        $puzzles  = collect($response->json('payload.puzzles'));

        $categories = $puzzles->pluck('category')->unique()->values()->all();
        $this->assertContains('tactical', $categories);
        $this->assertContains('positional', $categories);
        $this->assertContains('opening', $categories);
    }

    public function test_generated_puzzles_persist_as_training_sessions(): void
    {
        $this->actingAs($this->user)->postJson("/api/training/generate/{$this->game->id}");
        $this->assertSame(3, TrainingSession::where('user_id', $this->user->id)->count());
    }

    public function test_generate_returns_empty_when_no_errors(): void
    {
        $cleanGame = Game::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->postJson("/api/training/generate/{$cleanGame->id}");
        $response->assertStatus(200);
        $this->assertEmpty($response->json('payload.puzzles'));
    }

    public function test_user_can_submit_correct_answer_to_puzzle(): void
    {
        $session = TrainingSession::create([
            'user_id'      => $this->user->id,
            'game_id'      => $this->game->id,
            'fen'          => 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3',
            'correct_move' => 'e2e4',
            'category'     => 'tactical',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/training/submit/{$session->id}", [
            'move' => 'e2e4',
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('payload.is_correct'));
        $this->assertTrue((bool) $session->fresh()->is_correct);
    }

    public function test_user_can_submit_incorrect_answer_to_puzzle(): void
    {
        $session = TrainingSession::create([
            'user_id'      => $this->user->id,
            'game_id'      => $this->game->id,
            'fen'          => 'rnbqkb1r/pppp1ppp/5n2/4p3/4P3/5N2/PPPP1PPP/RNBQKB1R w KQkq - 2 3',
            'correct_move' => 'e2e4',
            'category'     => 'tactical',
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/training/submit/{$session->id}", [
            'move' => 'h2h3',
        ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('payload.is_correct'));
        $this->assertFalse((bool) $session->fresh()->is_correct);
    }

    public function test_submit_validates_required_move_parameter(): void
    {
        $session = TrainingSession::create([
            'user_id'      => $this->user->id,
            'fen'          => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1',
            'correct_move' => 'e2e4',
            'category'     => 'tactical',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/training/submit/{$session->id}", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['move']);
    }

    public function test_progress_endpoint_aggregates_by_category(): void
    {
        // Create some completed sessions
        TrainingSession::create([
            'user_id' => $this->user->id, 'fen' => 'x', 'correct_move' => 'a',
            'user_move' => 'a', 'is_correct' => true, 'category' => 'tactical',
        ]);
        TrainingSession::create([
            'user_id' => $this->user->id, 'fen' => 'x', 'correct_move' => 'a',
            'user_move' => 'b', 'is_correct' => false, 'category' => 'tactical',
        ]);
        TrainingSession::create([
            'user_id' => $this->user->id, 'fen' => 'x', 'correct_move' => 'a',
            'user_move' => 'a', 'is_correct' => true, 'category' => 'positional',
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/training/progress');
        $response->assertStatus(200);
        $response->assertJsonStructure(['payload' => ['by_category', 'trend']]);

        $byCat = collect($response->json('payload.by_category'));
        $this->assertGreaterThanOrEqual(2, $byCat->count());
    }

    public function test_guest_cannot_access_training(): void
    {
        $this->postJson("/api/training/generate/{$this->game->id}")->assertStatus(401);
        $this->getJson('/api/training/progress')->assertStatus(401);
    }

    // ----------------- Opening training (req 2.2.15) -----------------

    public function test_opening_training_returns_user_weakest_openings(): void
    {
        // Seed canonical openings the user might have played
        \App\Models\Opening::create([
            'eco' => 'B20', 'category' => 'B',
            'name' => 'Sicilian Defence', 'name_lv' => 'Sicīliešu aizsardzība',
            'moves' => 'e4 c5', 'sort_order' => 0,
        ]);
        \App\Models\Opening::create([
            'eco' => 'C60', 'category' => 'C',
            'name' => 'Ruy Lopez', 'name_lv' => 'Spāņu partija',
            'moves' => 'e4 e5 Nf3 Nc6 Bb5', 'sort_order' => 0,
        ]);

        // 4 losses with the Sicilian → 0% win rate
        \App\Models\Game::factory()->count(4)->create([
            'user_id'      => $this->user->id,
            'opening_name' => 'Sicilian Defence',
            'opening_eco'  => 'B20',
            'result'       => '0-1',
            'user_color'   => 'white',
        ]);
        // 3 wins with the Ruy Lopez → 100% win rate
        \App\Models\Game::factory()->count(3)->create([
            'user_id'      => $this->user->id,
            'opening_name' => 'Ruy Lopez',
            'opening_eco'  => 'C60',
            'result'       => '1-0',
            'user_color'   => 'white',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/training/openings');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'payload' => [
                'weak_openings' => [
                    '*' => ['opening_name', 'opening_eco', 'win_rate', 'total', 'moves', 'name_lv'],
                ],
            ],
        ]);

        $weak = $response->json('payload.weak_openings');
        $this->assertNotEmpty($weak);
        // The Sicilian (worst) must come first
        $this->assertSame('Sicilian Defence', $weak[0]['opening_name']);
        $this->assertEquals(0, $weak[0]['win_rate']);
        $this->assertNotEmpty($weak[0]['moves']);
    }

    public function test_opening_training_skips_openings_with_too_few_games(): void
    {
        \App\Models\Opening::create([
            'eco' => 'C00', 'category' => 'C',
            'name' => 'French Defence', 'name_lv' => 'Franču aizsardzība',
            'moves' => 'e4 e6', 'sort_order' => 0,
        ]);
        // Just 1 game — below min_games threshold of 2
        \App\Models\Game::factory()->create([
            'user_id'      => $this->user->id,
            'opening_name' => 'French Defence',
            'result'       => '0-1',
            'user_color'   => 'white',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/training/openings');
        $response->assertStatus(200);
        $this->assertEmpty($response->json('payload.weak_openings'));
    }

    public function test_opening_training_returns_empty_for_user_with_no_games(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/training/openings');
        $response->assertStatus(200);
        $this->assertEmpty($response->json('payload.weak_openings'));
    }

    public function test_opening_training_drops_openings_without_canonical_match(): void
    {
        // 3 losses with an opening not in the canonical openings table
        \App\Models\Game::factory()->count(3)->create([
            'user_id'      => $this->user->id,
            'opening_name' => 'My Made-Up Opening',
            'opening_eco'  => 'Z99',
            'result'       => '0-1',
            'user_color'   => 'white',
        ]);

        $response = $this->actingAs($this->user)->postJson('/api/training/openings');
        $response->assertStatus(200);
        // Filtered out because there's no canonical record to attach a move sequence
        $this->assertEmpty($response->json('payload.weak_openings'));
    }

    public function test_guest_cannot_request_opening_training(): void
    {
        $this->postJson('/api/training/openings')->assertStatus(401);
    }
}
