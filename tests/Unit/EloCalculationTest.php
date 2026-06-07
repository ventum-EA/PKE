<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\EloService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests ELO calculation logic via EloService, which is now the single
 * source of truth for all rating changes (single-player, multiplayer,
 * and training). Previously MultiplayerService had its own hardcoded
 * K=32; now it delegates to EloService for consistency.
 */
class EloCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper: create a user with a specific ELO and game count, then
     * process a game result against Stockfish to measure the change.
     */
    private function calcEloChange(int $playerElo, int $opponentSkill, float $score): int
    {
        $user = User::factory()->create(['elo_rating' => $playerElo]);
        $user->assignRole('user');

        $service = $this->app->make(EloService::class);
        $result = $service->processGameResult($user->id, $opponentSkill, $score);

        return $result['change'];
    }

    public function test_win_produces_positive_change(): void
    {
        $change = $this->calcEloChange(1200, 10, 1.0); // skill 10 ≈ 1600 ELO
        $this->assertGreaterThan(0, $change);
    }

    public function test_loss_produces_negative_change(): void
    {
        $change = $this->calcEloChange(1200, 10, 0.0);
        $this->assertLessThan(0, $change);
    }

    public function test_draw_against_higher_rated_produces_gain(): void
    {
        // Drawing against a much stronger opponent should gain ELO
        $change = $this->calcEloChange(1000, 15, 0.5); // skill 15 ≈ 2100 ELO
        $this->assertGreaterThanOrEqual(0, $change);
    }

    public function test_elo_stays_within_bounds(): void
    {
        $user = User::factory()->create(['elo_rating' => 150]);
        $user->assignRole('user');

        $service = $this->app->make(EloService::class);
        $result = $service->processGameResult($user->id, 20, 0.0); // Lose to max Stockfish

        $this->assertGreaterThanOrEqual(100, $result['new_elo']); // MIN_ELO = 100
    }

    public function test_underdog_win_gains_more_than_favorite(): void
    {
        $underdogChange = $this->calcEloChange(800, 15, 1.0);   // 800 beats ~2100
        $favoriteChange = $this->calcEloChange(2000, 5, 1.0);   // 2000 beats ~1000
        $this->assertGreaterThan($favoriteChange, $underdogChange);
    }

    public function test_change_is_integer(): void
    {
        $change = $this->calcEloChange(1337, 12, 1.0);
        $this->assertIsInt($change);
    }

    public function test_multiplayer_uses_consistent_k_factors(): void
    {
        $white = User::factory()->create(['elo_rating' => 1200]);
        $black = User::factory()->create(['elo_rating' => 1200]);
        $white->assignRole('user');
        $black->assignRole('user');

        $service = $this->app->make(EloService::class);
        $result = $service->processMultiplayerResult($white->id, $black->id, 1.0, 999);

        // K=40 for new players, expected=0.5, so change = 40*0.5 = 20
        $this->assertEquals(20, $result['white_change']);
        $this->assertEquals(-20, $result['black_change']);
    }

    public function test_multiplayer_symmetry(): void
    {
        $white = User::factory()->create(['elo_rating' => 1400]);
        $black = User::factory()->create(['elo_rating' => 1200]);
        $white->assignRole('user');
        $black->assignRole('user');

        $service = $this->app->make(EloService::class);
        $result = $service->processMultiplayerResult($white->id, $black->id, 1.0, 999);

        // Favorite wins: white gains less than black would if black won
        $this->assertGreaterThan(0, $result['white_change']);
        $this->assertLessThan(0, $result['black_change']);
    }

    public function test_training_never_loses_elo(): void
    {
        $user = User::factory()->create(['elo_rating' => 1200]);
        $user->assignRole('user');

        $service = $this->app->make(EloService::class);

        // Even 0 correct should not lose ELO
        $result = $service->processTrainingResult($user->id, 0, 5);
        $this->assertEquals(0, $result['change']);
    }
}
