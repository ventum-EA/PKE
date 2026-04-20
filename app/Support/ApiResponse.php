<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponse
{
    protected function success(string $message, array $payload = [], int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'payload' => $payload,
        ], $status);
    }

    protected function error(string $message, int $status = Response::HTTP_BAD_REQUEST, array $payload = []): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'payload' => $payload,
        ], $status);
    }
}
