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

        return $this->success('Treniņu uzdevumi ģenerēti', $result);
    }

    public function submit(int $sessionId, Request $request): JsonResponse
    {
        $request->validate(['move' => 'required|string|max:10']);
        $result = $this->trainingService->submitAnswer($sessionId, $request->input('move'));

        return $this->success($result['is_correct'] ? 'Pareizi!' : 'Nepareizi', $result);
    }

    public function progress(): JsonResponse
    {
        return $this->success('Treniņu progress ielādēts', $this->trainingService->getProgress());
    }

    public function progressReport(): JsonResponse
    {
        $report = $this->trainingService->progressReport();

        return $this->success(
            $report['has_data'] ? 'Progresa atskaite ģenerēta' : ($report['message'] ?? 'Nav datu'),
            $report
        );
    }

    public function complete(Request $request): JsonResponse
    {
        return $this->success('Treniņu sesija pabeigta', []);
    }

    public function generateOpeningTraining(Request $request): JsonResponse
    {
        $minGames = (int) $request->get('min_games', 2);
        $limit    = (int) $request->get('limit', 3);

        $result = $this->trainingService->generateOpeningTraining($minGames, $limit);

        return $this->success(
            count($result['weak_openings']) > 0
                ? 'Atklātņu treniņa ieteikumi sagatavoti'
                : ($result['message'] ?? 'Nav pieejamu datu'),
            $result
        );
    }
}
