<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TrainingController extends Controller
{
    public function __construct(
        protected TrainingService $trainingService
    ) {}

    public function generate(int $gameId): JsonResponse
    {
        $result = $this->trainingService->generatePuzzleFromErrors($gameId);

        return response()->json([
            'message' => 'Treniņu uzdevumi ģenerēti',
            'payload' => $result,
        ], Response::HTTP_OK);
    }

    public function submit(int $sessionId, Request $request): JsonResponse
    {
        $request->validate(['move' => 'required|string|max:10']);
        $result = $this->trainingService->submitAnswer($sessionId, $request->input('move'));

        return response()->json([
            'message' => $result['is_correct'] ? 'Pareizi!' : 'Nepareizi, mēģiniet vēlreiz.',
            'payload' => $result,
        ], Response::HTTP_OK);
    }

    public function progress(): JsonResponse
    {
        return response()->json([
            'message' => 'Treniņu progress ielādēts',
            'payload' => $this->trainingService->getProgress(),
        ], Response::HTTP_OK);
    }
}
