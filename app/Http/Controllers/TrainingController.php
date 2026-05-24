<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TrainingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrainingController extends Controller
{
    use ApiResponse;
    public function __construct(
        protected TrainingService $trainingService
    ) {}

    public function generate(int $gameId, Request $request): JsonResponse
    {
        $game = \App\Models\Game::findOrFail($gameId);
        if ($game->getUserId() !== $request->user()->id) {
            return $this->error('Nav piekļuves', Response::HTTP_FORBIDDEN);
        }

        $result = $this->trainingService->generatePuzzleFromErrors($gameId);

        return $this->success('Treniņu uzdevumi ģenerēti', ['payload' => $result]);
    }

    public function submit(int $sessionId, Request $request): JsonResponse
    {
        $request->validate(['move' => 'required|string|max:10']);
        $result = $this->trainingService->submitAnswer($sessionId, $request->input('move'));

        return $this->success('OK', ['message' => $result['is_correct'] ? 'Pareizi!' : 'Nepareizi, mēģiniet vēlreiz.',
            'payload' => $result]);
    }

    public function progress(): JsonResponse
    {
        return $this->success('Treniņu progress ielādēts', ['payload' => $this->trainingService->getProgress()]);
    }

    /**
     * Detailed progress report with before/after comparison.
     * Fulfils spec §2.2.19.
     */
    public function progressReport(): JsonResponse
    {
        $report = $this->trainingService->progressReport();

        return $this->success('OK', ['message' => $report['has_data'] ? 'Progresa atskaite ģenerēta' : ($report['message'] ?? 'Nav datu'),
            'payload' => $report]);
    }

    /**
     * Mark a training session as complete (optional endpoint for session tracking).
     */
    public function complete(Request $request): JsonResponse
    {
        return $this->success('Treniņu sesija pabeigta', ['payload' => []]);
    }

    /**
     * Generate a personalized opening training session from the user's
     * weakest openings (lowest win rate, ≥2 games played).
     */
    public function generateOpeningTraining(Request $request): JsonResponse
    {
        $minGames = (int) $request->get('min_games', 2);
        $limit    = (int) $request->get('limit', 3);

        $result = $this->trainingService->generateOpeningTraining($minGames, $limit);

        return $this->success('OK', ['message' => count($result['weak_openings']) > 0
                ? 'Atklātņu treniņa ieteikumi sagatavoti'
                : ($result['message'] ?? 'Nav pieejamu datu'),
            'payload' => $result]);
    }
}
