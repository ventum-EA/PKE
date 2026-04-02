<?php

namespace App\Repositories;

use App\Models\GameMove;

class GameMoveRepository
{
    public function findById(int $id): GameMove
    {
        return GameMove::findOrFail($id);
    }

    public function store(array $data): GameMove
    {
        return GameMove::create($data);
    }

    public function bulkInsert(array $moves): bool
    {
        return GameMove::insert($moves);
    }

    public function getByGameId(int $gameId): \Illuminate\Database\Eloquent\Collection
    {
        return GameMove::where('game_id', $gameId)
            ->orderBy('move_number')
            ->orderByRaw("CASE WHEN color = 'white' THEN 0 ELSE 1 END")
            ->get();
    }

    public function getErrorsByGameId(int $gameId): \Illuminate\Database\Eloquent\Collection
    {
        return GameMove::where('game_id', $gameId)
            ->whereIn('classification', ['inaccuracy', 'mistake', 'blunder'])
            ->orderBy('move_number')
            ->get();
    }

    public function deleteByGameId(int $gameId): int
    {
        return GameMove::where('game_id', $gameId)->delete();
    }
}
