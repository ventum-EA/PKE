<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Game;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface GameRepositoryInterface
{
    public function findById(int $id): Game;
    public function findByShareToken(string $token): Game;
    public function store(array $data): Game;
    public function update(Game $game, array $data): bool;
    public function delete(Game $game): bool;
    public function getFilteredGames(int $perPage): LengthAwarePaginator;
    public function getPlayerStats(int $userId): array;
    public function getOpeningStats(int $userId): array;
    public function getErrorStats(int $userId): array;
    public function getProgressTrend(int $userId): array;
}
