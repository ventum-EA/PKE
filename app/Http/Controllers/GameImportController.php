<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GameImportService;
use App\Services\PatternDetectionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameImportController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GameImportService $importService,
        protected PatternDetectionService $patternService,
    ) {}

    /**
     * POST /api/games/import — import games from an external platform.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'source'   => 'required|in:lichess,chesscom',
            'username' => 'required|string|max:40|regex:/^[a-zA-Z0-9_-]+$/',
            'max'      => 'nullable|integer|min:1|max:50',
        ]);

        $userId = $request->user()->id;
        $source = $request->input('source');
        $username = $request->input('username');
        $max = $request->integer('max', 30);

        $result = match ($source) {
            'lichess' => $this->importService->importFromLichess($userId, $username, $max),
            'chesscom' => $this->importService->importFromChesscom($userId, $username, $max),
        };

        $message = $result['imported'] > 0
            ? "Imported {$result['imported']} games from {$source}."
            : 'No new games to import.';

        return $this->success($message, [
            'imported' => $result['imported'],
            'skipped'  => $result['skipped'],
            'errors'   => $result['errors'],
        ]);
    }

    /**
     * POST /api/patterns/detect — run pattern detection on user's games.
     */
    public function detectPatterns(Request $request): JsonResponse
    {
        $patterns = $this->patternService->detect($request->user()->id);

        return $this->success('Patterns detected.', [
            'patterns' => $patterns,
            'count'    => count($patterns),
        ]);
    }

    /**
     * GET /api/patterns — get existing detected patterns.
     */
    public function getPatterns(Request $request): JsonResponse
    {
        $patterns = $this->patternService->getPatterns($request->user()->id);

        return $this->success('Patterns loaded.', [
            'patterns' => $patterns,
        ]);
    }
}
