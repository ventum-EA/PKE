<?php

namespace Tests\Unit;

use App\Services\MultiplayerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Access the private calculateEloChange method via reflection.
     */
    private function calcElo(int $playerElo, int $opponentElo, float $score): int
    {
        $service = $this->app->make(MultiplayerService::class);
        $ref = new \ReflectionMethod($service, 'calculateEloChange');
        $ref->setAccessible(true);
        return $ref->invoke($service, $playerElo, $opponentElo, $score);
    }

    public function test_equal_rating_win(): void
    {
        $change = $this->calcElo(1200, 1200, 1.0);
        $this->assertEquals(16, $change); // K=32, expected=0.5, change=32*0.5=16
    }

    public function test_equal_rating_loss(): void
    {
        $change = $this->calcElo(1200, 1200, 0.0);
        $this->assertEquals(-16, $change);
    }

    public function test_equal_rating_draw(): void
    {
        $change = $this->calcElo(1200, 1200, 0.5);
        $this->assertEquals(0, $change);
    }

    public function test_hard_cap_maximum_50(): void
    {
        // Very low rated player beating very high rated player
        $change = $this->calcElo(400, 2800, 1.0);
        $this->assertLessThanOrEqual(50, $change);
    }

    public function test_hard_cap_minimum_1_on_win(): void
    {
        // Very high rated player beating very low rated player
        $change = $this->calcElo(2800, 400, 1.0);
        $this->assertGreaterThanOrEqual(1, $change);
    }

    public function test_hard_cap_minimum_negative_1_on_loss(): void
    {
        // Very low rated player losing to very high rated player (tiny expected loss)
        $change = $this->calcElo(400, 2800, 0.0);
        $this->assertLessThanOrEqual(-1, $change);
    }

    public function test_hard_cap_negative_maximum_50(): void
    {
        // Very high rated player losing to very low rated player
        $change = $this->calcElo(2800, 400, 0.0);
        $this->assertGreaterThanOrEqual(-50, $change);
    }

    public function test_symmetry(): void
    {
        $winChange = $this->calcElo(1200, 1400, 1.0);
        $lossChange = $this->calcElo(1400, 1200, 0.0);
        // Winner gains approximately what loser loses (may differ by 1 due to rounding)
        $this->assertEqualsWithDelta(-$lossChange, $winChange, 2);
    }

    public function test_underdog_win_gains_more(): void
    {
        $underdogWin = $this->calcElo(1000, 1400, 1.0);
        $favoriteWin = $this->calcElo(1400, 1000, 1.0);
        $this->assertGreaterThan($favoriteWin, $underdogWin);
    }

    public function test_change_is_integer(): void
    {
        $change = $this->calcElo(1337, 1421, 1.0);
        $this->assertIsInt($change);
    }
}
