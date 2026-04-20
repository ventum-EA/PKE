<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\GameMove;
use Illuminate\Database\Eloquent\Collection;

interface GameMoveRepositoryInterface
{
    public function findById(int $id): GameMove;
    public function store(array $data): GameMove;
    public function bulkInsert(array $moves): bool;
    public function getByGameId(int $gameId): Collection;
    public function getErrorsByGameId(int $gameId): Collection;
    public function deleteByGameId(int $gameId): int;
}
