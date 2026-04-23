<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Game;
use App\Models\GameImport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GameImportService
{
    /**
     * Import recent games from Lichess for a given username.
     *
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function importFromLichess(int $userId, string $username, int $max = 50): array
    {
        $response = Http::withHeaders([
                'Accept' => 'application/x-ndjson',
            ])
            ->timeout(30)
            ->get("https://lichess.org/api/games/user/{$username}", [
                'max'        => min($max, 50),
                'pgnInJson'  => true,
                'opening'    => true,
                'clocks'     => false,
                'evals'      => false,
            ]);

        if (!$response->successful()) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Lichess API returned ' . $response->status()]];
        }

        // Lichess returns NDJSON (one JSON object per line)
        $lines = array_filter(explode("\n", $response->body()));
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($lines as $line) {
            $game = json_decode($line, true);
            if (!$game || !isset($game['id'])) continue;

            // Check if already imported
            $exists = GameImport::where('user_id', $userId)
                ->where('source', 'lichess')
                ->where('source_game_id', $game['id'])
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                $pgn = $game['pgn'] ?? '';
                if (empty($pgn)) { $skipped++; continue; }

                $result = $game['winner'] ?? null;
                $resultStr = match ($result) {
                    'white' => '1-0',
                    'black' => '0-1',
                    default => $game['status'] === 'draw' ? '1/2-1/2' : '*',
                };

                $players = $game['players'] ?? [];
                $whiteName = $players['white']['user']['name'] ?? 'White';
                $blackName = $players['black']['user']['name'] ?? 'Black';
                $userColor = strtolower($whiteName) === strtolower($username) ? 'white' : 'black';

                $opening = $game['opening'] ?? [];

                $savedGame = Game::create([
                    'user_id'      => $userId,
                    'pgn'          => $pgn,
                    'white_player' => $whiteName,
                    'black_player' => $blackName,
                    'result'       => $resultStr,
                    'user_color'   => $userColor,
                    'opening_name' => $opening['name'] ?? null,
                    'opening_eco'  => $opening['eco'] ?? null,
                    'total_moves'  => $game['moves'] ? count(explode(' ', $game['moves'])) : 0,
                    'is_analyzed'  => false,
                    'played_at'    => isset($game['createdAt']) ? date('Y-m-d H:i:s', $game['createdAt'] / 1000) : now(),
                ]);

                GameImport::create([
                    'user_id'         => $userId,
                    'source'          => 'lichess',
                    'source_username' => $username,
                    'source_game_id'  => $game['id'],
                    'game_id'         => $savedGame->id,
                    'imported_at'     => now(),
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Game {$game['id']}: {$e->getMessage()}";
            }
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Import recent games from Chess.com for a given username.
     *
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function importFromChesscom(int $userId, string $username, int $max = 50): array
    {
        // Chess.com API returns games by month
        $now = now();
        $year = $now->year;
        $month = str_pad((string) $now->month, 2, '0', STR_PAD_LEFT);

        $response = Http::timeout(30)
            ->get("https://api.chess.com/pub/player/{$username}/games/{$year}/{$month}");

        if (!$response->successful()) {
            // Try previous month
            $prev = $now->subMonth();
            $response = Http::timeout(30)
                ->get("https://api.chess.com/pub/player/{$username}/games/{$prev->year}/" . str_pad((string) $prev->month, 2, '0', STR_PAD_LEFT));

            if (!$response->successful()) {
                return ['imported' => 0, 'skipped' => 0, 'errors' => ['Chess.com API returned ' . $response->status()]];
            }
        }

        $data = $response->json();
        $games = array_slice(array_reverse($data['games'] ?? []), 0, $max);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($games as $game) {
            $url = $game['url'] ?? '';
            $sourceId = Str::afterLast($url, '/') ?: md5(json_encode($game));

            $exists = GameImport::where('user_id', $userId)
                ->where('source', 'chesscom')
                ->where('source_game_id', $sourceId)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            try {
                $pgn = $game['pgn'] ?? '';
                if (empty($pgn)) { $skipped++; continue; }

                $whiteUser = $game['white']['username'] ?? 'White';
                $blackUser = $game['black']['username'] ?? 'Black';
                $userColor = strtolower($whiteUser) === strtolower($username) ? 'white' : 'black';

                $whiteResult = $game['white']['result'] ?? '';
                $resultStr = match (true) {
                    $whiteResult === 'win' => '1-0',
                    ($game['black']['result'] ?? '') === 'win' => '0-1',
                    in_array($whiteResult, ['agreed', 'stalemate', 'repetition', 'insufficient', 'timevsinsufficient']) => '1/2-1/2',
                    default => '*',
                };

                // Extract opening from PGN headers
                $openingName = null;
                $openingEco = null;
                if (preg_match('/\[ECO "([^"]+)"\]/', $pgn, $m)) $openingEco = $m[1];
                if (preg_match('/\[Opening "([^"]+)"\]/', $pgn, $m)) $openingName = $m[1];

                $savedGame = Game::create([
                    'user_id'      => $userId,
                    'pgn'          => $pgn,
                    'white_player' => $whiteUser,
                    'black_player' => $blackUser,
                    'result'       => $resultStr,
                    'user_color'   => $userColor,
                    'opening_name' => $openingName,
                    'opening_eco'  => $openingEco,
                    'total_moves'  => 0,
                    'is_analyzed'  => false,
                    'played_at'    => isset($game['end_time']) ? date('Y-m-d H:i:s', $game['end_time']) : now(),
                ]);

                GameImport::create([
                    'user_id'         => $userId,
                    'source'          => 'chesscom',
                    'source_username' => $username,
                    'source_game_id'  => $sourceId,
                    'game_id'         => $savedGame->id,
                    'imported_at'     => now(),
                ]);

                $imported++;
            } catch (\Throwable $e) {
                $errors[] = "Game {$sourceId}: {$e->getMessage()}";
            }
        }

        return compact('imported', 'skipped', 'errors');
    }
}
