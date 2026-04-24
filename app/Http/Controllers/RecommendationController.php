<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RecommendationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class RecommendationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected RecommendationService $recommendationService
    ) {}

    /**
     * GET /api/recommendations
     *
     * Returns personalized training recommendations based on the
     * authenticated user's error statistics and opening performance.
     * Fulfils spec §2.2.6.
     */
    public function index(): JsonResponse
    {
        $result = $this->recommendationService->generate();

        return $this->success('Ieteikumi ģenerēti', $result);
    }
}
