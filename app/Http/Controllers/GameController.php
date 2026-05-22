<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GameData;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Http\Resources\GameMoveResource;
use App\Http\Resources\GameResource;
use App\Repositories\GameMoveRepository;
use App\Repositories\GameRepository;
use App\Services\GameService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GameController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected GameService $gameService,
        protected GameRepository $gameRepo,
        protected GameMoveRepository $moveRepo
    ) {}

    public function store(StoreGameRequest $request, GameData $gameData): JsonResponse
    {
        $result = $this->gameService->createGame($gameData);
        $game = $result['game'];
        \App\Models\AuditLog::record('game.create', $game);

        return $this->success('Partija saglabāta veiksmīgi!', [
            'game' => new GameResource($game),
        ], Response::HTTP_CREATED);
    }

    public function modify(UpdateGameRequest $request, GameData $gameData): JsonResponse
    {
        $game = $this->gameService->updateGame($gameData);

        return $this->success('Partija atjaunināta veiksmīgi', ['id' => $game->getId()]);
    }

    /**
     * @OA\Get(
     *     path="/games",
     *     tags={"Games"},
     *     summary="Lietotāja partiju saraksts",
     *     description="Atgriež autentificēto lietotāja partijas ar lapošanu. Atbalsta filtrēšanu un kārtošanu caur Spatie Query Builder.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="filter[result]", in="query", @OA\Schema(type="string", enum={"1-0","0-1","1/2-1/2"}), description="Filtrēt pēc rezultāta"),
     *     @OA\Parameter(name="filter[is_analyzed]", in="query", @OA\Schema(type="boolean"), description="Tikai analizētās"),
     *     @OA\Parameter(name="sort", in="query", @OA\Schema(type="string", example="-created_at"), description="Kārtošanas lauks (- prefikss = dilstoši)"),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", example=1)),
     *     @OA\Parameter(name="perPage", in="query", @OA\Schema(type="integer", example=12)),
     *     @OA\Response(
     *         response=200,
     *         description="Partiju saraksts",
     *         @OA\JsonContent(
     *             @OA\Property(property="games", type="object",
     *                 @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Game")),
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="last_page", type="integer"),
     *                 @OA\Property(property="total", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Neautorizēts")
     * )
     */
    public function retrieve(Request $request): JsonResponse
    {
        $perPage = min((int) $request->get('perPage', 12), 100);
        $games = $this->gameRepo->getFilteredGames($perPage);

        return $this->success('Partijas ielādētas veiksmīgi', [
            'games' => GameResource::collection($games)->response()->getData(true),
        ]);
    }

    private function authorizeGameAccess(int $id): \App\Models\Game
    {
        $game = $this->gameRepo->findById($id);
        if ($game->getUserId() !== (int) request()->user()->id) {
            abort(403, 'Nav piekļuves šai partijai');
        }
        return $game;
    }

    /**
     * @OA\Get(
     *     path="/game/{id}",
     *     tags={"Games"},
     *     summary="Vienas partijas detaļas",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response=200,
     *         description="Partija atrasta",
     *         @OA\JsonContent(
     *             @OA\Property(property="game", ref="#/components/schemas/Game")
     *         )
     *     ),
     *     @OA\Response(response=403, description="Nav piekļuves"),
     *     @OA\Response(response=404, description="Partija nav atrasta")
     * )
     */
    public function getOne(int $id): JsonResponse
    {
        $game = $this->authorizeGameAccess($id);

        return $this->success('Partija ielādēta', [
            'game' => new GameResource($game),
        ]);
    }

    public function delete(int $id): JsonResponse
    {
        $game = $this->authorizeGameAccess($id);

        \App\Models\AuditLog::record('game.delete', $game, [
            'opening' => $game->opening_name,
            'result' => $game->result,
        ]);
        $this->gameRepo->delete($game);

        return $this->success('Partija dzēsta veiksmīgi', ['id' => $id]);
    }

    public function analyze(int $id, Request $request): JsonResponse
    {
        $this->authorizeGameAccess($id);

        $maxDepth = (int) config('chess.stockfish.max_depth', 30);
        $depth = min((int) $request->get('depth', 15), $maxDepth);
        $serverSide = $request->boolean('server', false);

        if ($serverSide) {
            \App\Jobs\AnalyzeGameJob::dispatch($id, $depth);
            return $this->success('Dziļā analīze ieplānota. Rezultāti parādīsies drīz.', [
                'queued' => true, 'game_id' => $id, 'depth' => $depth,
            ]);
        }

        return $this->success('Analīze pabeigta', $this->gameService->analyzeGame($id, $depth));
    }

    public function getMoves(int $id): JsonResponse
    {
        $this->authorizeGameAccess($id);

        return $this->success('Gājieni ielādēti', [
            'moves' => GameMoveResource::collection($this->moveRepo->getByGameId($id)),
        ]);
    }

    /**
     * Save client-side (WASM) analysis results to the database.
     */
    public function saveMoves(int $id, Request $request): JsonResponse
    {
        $this->authorizeGameAccess($id);

        $request->validate([
            'moves' => 'required|array|min:1|max:600',
            'moves.*.move_number' => 'required|integer',
            'moves.*.color' => 'required|in:white,black',
            'moves.*.move_san' => 'required|string|max:10',
            'moves.*.eval_before' => 'nullable|numeric',
            'moves.*.eval_after' => 'nullable|numeric',
            'moves.*.eval_diff' => 'nullable|numeric',
            'moves.*.best_move' => 'nullable|string|max:10',
            'moves.*.classification' => 'nullable|string',
            'moves.*.error_category' => 'nullable|string',
            'moves.*.explanation' => 'nullable|string|max:500',
            'moves.*.fen_before' => 'nullable|string',
            'moves.*.fen_after' => 'nullable|string',
        ]);

        $this->moveRepo->deleteByGameId($id);

        $now = now();
        $movesData = collect($request->input('moves'))->map(fn($m) => [
            'game_id' => $id,
            'move_number' => $m['move_number'],
            'color' => $m['color'],
            'move_san' => $m['move_san'],
            'fen_before' => $m['fen_before'] ?? null,
            'fen_after' => $m['fen_after'] ?? null,
            'eval_before' => $m['eval_before'] ?? null,
            'eval_after' => $m['eval_after'] ?? null,
            'eval_diff' => $m['eval_diff'] ?? null,
            'best_move' => $m['best_move'] ?? null,
            'classification' => $m['classification'] ?? null,
            'error_category' => $m['error_category'] ?? null,
            'explanation' => $m['explanation'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        $this->moveRepo->bulkInsert($movesData);
        $this->gameRepo->update($this->gameRepo->findById($id), ['is_analyzed' => true]);

        return $this->success('Analīzes dati saglabāti', [
            'saved' => count($movesData), 'game_id' => $id,
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        return $this->success('Statistika ielādēta', $this->gameService->getDashboardStats());
    }

    public function share(int $id, Request $request): JsonResponse
    {
        $game = $this->authorizeGameAccess($id);

        return $this->success('Kopīgošanas saite izveidota', [
            'share_url' => url("/shared/{$game->generateShareToken()}"),
        ]);
    }

    public function getShared(string $token): JsonResponse
    {
        return $this->success('Kopīgotā partija ielādēta', [
            'game' => new GameResource($this->gameRepo->findByShareToken($token)),
        ]);
    }

    /**
     * Download the PGN of a game as a .pgn file.
     */
    public function download(int $id, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $game = $this->authorizeGameAccess($id);

        $filename = sprintf(
            'game-%d-%s-vs-%s.pgn',
            $game->getId(),
            preg_replace('/[^A-Za-z0-9_-]/', '_', $game->getWhitePlayer() ?? 'white'),
            preg_replace('/[^A-Za-z0-9_-]/', '_', $game->getBlackPlayer() ?? 'black')
        );

        $headers = [
            '[Event "' . ($game->getOpeningName() ?? 'Casual Game') . '"]',
            '[White "' . ($game->getWhitePlayer() ?? '?') . '"]',
            '[Black "' . ($game->getBlackPlayer() ?? '?') . '"]',
            '[Result "' . $game->getResult() . '"]',
        ];
        if ($game->getOpeningEco()) {
            $headers[] = '[ECO "' . $game->getOpeningEco() . '"]';
        }
        if ($game->getPlayedAt()) {
            $headers[] = '[Date "' . $game->getPlayedAt() . '"]';
        }

        $pgnBody = $game->getPgn();
        $pgn = str_contains($pgnBody, '[White ') ? $pgnBody : implode("\n", $headers) . "\n\n" . $pgnBody;

        return response($pgn, Response::HTTP_OK, [
            'Content-Type' => 'application/x-chess-pgn',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function eloHistory(Request $request): JsonResponse
    {
        $limit = min((int) $request->get('limit', 20), 100);
        $history = \Illuminate\Support\Facades\DB::table('elo_history')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $this->success('ELO vēsture ielādēta', ['history' => $history]);
    }
}
