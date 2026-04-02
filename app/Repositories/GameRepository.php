<?php

namespace App\Repositories;

use App\Models\Game;
use Illuminate\Database\ConnectionInterface;
use Spatie\QueryBuilder\QueryBuilder;

class GameRepository
{
    public function __construct(
        protected ConnectionInterface $db
    ) {}

    public function findById(int $id): Game
    {
        return Game::findOrFail($id);
    }

    public function findByShareToken(string $token): Game
    {
        return Game::where('share_token', $token)->firstOrFail();
    }

    public function store(array $data): Game
    {
        return Game::create($data);
    }

    public function update(Game $game, array $data): bool
    {
        return $game->update($data);
    }

    public function delete(Game $game): bool
    {
        return $game->delete();
    }

    public function getFilteredGames(int $perPage): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return QueryBuilder::for(Game::class)
            ->allowedFilters([
                'result',
                'user_color',
                'is_analyzed',
                'user_id',
                \Spatie\QueryBuilder\AllowedFilter::partial('opening_name'),
                \Spatie\QueryBuilder\AllowedFilter::partial('opening_eco'),
                \Spatie\QueryBuilder\AllowedFilter::callback('player', function ($query, $value) {
                    $like = '%' . $value . '%';
                    $query->where(function ($q) use ($like) {
                        $q->where('white_player', 'like', $like)
                          ->orWhere('black_player', 'like', $like);
                    });
                }),
                \Spatie\QueryBuilder\AllowedFilter::callback('played_from', function ($query, $value) {
                    $query->whereDate('played_at', '>=', $value);
                }),
                \Spatie\QueryBuilder\AllowedFilter::callback('played_to', function ($query, $value) {
                    $query->whereDate('played_at', '<=', $value);
                }),
            ])
            ->allowedSorts(['created_at', 'played_at', 'total_moves', 'opening_name'])
            ->defaultSort('-created_at')
            ->paginate($perPage)
            ->appends(request()->query());
    }

    public function getPlayerStats(int $userId): array
    {
        $results = $this->db->table('games')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->select('result', 'user_color', $this->db->raw('count(*) as total'))
            ->groupBy('result', 'user_color')
            ->get();

        $wins = 0; $losses = 0; $draws = 0; $total = 0;
        foreach ($results as $r) {
            $total += $r->total;
            if (($r->result === '1-0' && $r->user_color === 'white') ||
                ($r->result === '0-1' && $r->user_color === 'black')) {
                $wins += $r->total;
            } elseif ($r->result === '1/2-1/2') {
                $draws += $r->total;
            } else {
                $losses += $r->total;
            }
        }

        return [
            'total_games' => $total,
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'win_rate' => $total > 0 ? round(($wins / $total) * 100, 1) : 0,
        ];
    }

    public function getOpeningStats(int $userId): array
    {
        return $this->db->table('games')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereNotNull('opening_name')
            ->select(
                'opening_name',
                'opening_eco',
                $this->db->raw('count(*) as total'),
                $this->db->raw("SUM(CASE WHEN (result = '1-0' AND user_color = 'white') OR (result = '0-1' AND user_color = 'black') THEN 1 ELSE 0 END) as wins"),
                $this->db->raw("SUM(CASE WHEN result = '1/2-1/2' THEN 1 ELSE 0 END) as draws"),
                $this->db->raw("SUM(CASE WHEN (result = '0-1' AND user_color = 'white') OR (result = '1-0' AND user_color = 'black') THEN 1 ELSE 0 END) as losses")
            )
            ->groupBy('opening_name', 'opening_eco')
            ->orderByDesc('total')
            ->limit(20)
            ->get()
            ->toArray();
    }

    public function getErrorStats(int $userId): array
    {
        return $this->db->table('game_moves')
            ->join('games', 'game_moves.game_id', '=', 'games.id')
            ->where('games.user_id', $userId)
            ->whereIn('game_moves.classification', ['inaccuracy', 'mistake', 'blunder'])
            ->select(
                'game_moves.error_category',
                'game_moves.classification',
                $this->db->raw('count(*) as total')
            )
            ->groupBy('game_moves.error_category', 'game_moves.classification')
            ->get()
            ->toArray();
    }

    public function getProgressTrend(int $userId): array
    {
        return $this->db->table('games')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('created_at', '>=', now()->subDays(90))
            ->select(
                $this->db->raw('DATE(created_at) as date'),
                $this->db->raw('count(*) as total_games'),
                $this->db->raw("SUM(CASE WHEN (result = '1-0' AND user_color = 'white') OR (result = '0-1' AND user_color = 'black') THEN 1 ELSE 0 END) as wins")
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
