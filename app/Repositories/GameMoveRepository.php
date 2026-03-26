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
            ->orderByRaw("FIELD(color, 'white', 'black')")
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
