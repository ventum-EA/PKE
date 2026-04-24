<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AchievementService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AchievementService $achievementService,
    ) {}

    /**
     * GET /api/achievements — list all achievements with user progress.
     */
    public function index(Request $request): JsonResponse
    {
        $achievements = $this->achievementService->getUserAchievements(
            $request->user()->id,
        );

        return $this->success('Achievements loaded.', [
            'achievements' => $achievements,
        ]);
    }

    /**
     * POST /api/achievements/check — check and award new achievements.
     * Called after game saves, analysis completions, training sessions.
     */
    public function check(Request $request): JsonResponse
    {
        $newlyUnlocked = $this->achievementService->checkAndAward(
            $request->user()->id,
        );

        return $this->success('Achievements checked.', [
            'newly_unlocked' => $newlyUnlocked,
        ]);
    }
}
