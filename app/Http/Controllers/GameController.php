<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\GameData;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Http\Resources\GameResource;
use App\Http\Resources\GameMoveResource;
use App\Repositories\GameRepository;
use App\Repositories\GameMoveRepository;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GameController extends Controller
{
    private const KEY_GAME = 'game';
    private const KEY_GAMES = 'games';
    private const KEY_PAYLOAD = 'payload';
    private const KEY_MESSAGE = 'message';

    public function __construct(
        protected GameService $gameService,
        protected GameRepository $gameRepo,
        protected GameMoveRepository $moveRepo
    ) {}

    public function store(StoreGameRequest $request, GameData $gameData): JsonResponse
    {
        $result = $this->gameService->createGame($gameData);
        $game = $result[self::KEY_GAME];
        \App\Models\AuditLog::record('game.create', $game);

        return response()->json([
            self::KEY_MESSAGE => 'Partija saglabāta veiksmīgi!',
            self::KEY_PAYLOAD => [
                self::KEY_GAME => new GameResource($game)
            ]
        ], Response::HTTP_OK);
    }

    public function modify(UpdateGameRequest $request, GameData $gameData): JsonResponse
    {
        $game = $this->gameService->updateGame($gameData);

        return response()->json([
            self::KEY_MESSAGE => 'Partija atjaunināta veiksmīgi',
            self::KEY_PAYLOAD => ['id' => $game->getId()]
        ], Response::HTTP_OK);
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
        $perPage = $request->get('perPage', 12);
        $games = $this->gameRepo->getFilteredGames((int) $perPage);

        return response()->json([
            self::KEY_MESSAGE => 'Partijas ielādētas veiksmīgi',
            self::KEY_PAYLOAD => [
                self::KEY_GAMES => GameResource::collection($games)->response()->getData(true),
            ]
        ], Response::HTTP_OK);
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
        $game = $this->gameRepo->findById($id);

        return response()->json([
            self::KEY_MESSAGE => 'Partija ielādēta',
            self::KEY_PAYLOAD => [
                self::KEY_GAME => new GameResource($game),
            ]
        ], Response::HTTP_OK);
    }

    public function delete(int $id): JsonResponse
    {
        $game = $this->gameRepo->findById($id);
        \App\Models\AuditLog::record('game.delete', $game, [
            'opening' => $game->opening_name,
            'result'  => $game->result,
        ]);
        $this->gameRepo->delete($game);

        return response()->json([
            self::KEY_MESSAGE => 'Partija dzēsta veiksmīgi',
            self::KEY_PAYLOAD => ['id' => $id]
        ], Response::HTTP_OK);
    }

    public function analyze(int $id, Request $request): JsonResponse
    {
        $depth = (int) $request->get('depth', 15);
        $serverSide = $request->boolean('server', false);

        if ($serverSide) {
            // Dispatch deep analysis job to queue
            \App\Jobs\AnalyzeGameJob::dispatch($id, $depth);
            return response()->json([
                self::KEY_MESSAGE => 'Dziļā analīze ieplānota. Rezultāti parādīsies drīz.',
                self::KEY_PAYLOAD => ['queued' => true, 'game_id' => $id, 'depth' => $depth]
            ], Response::HTTP_OK);
        }

        // Client-side analysis results are saved via this endpoint
        $result = $this->gameService->analyzeGame($id, $depth);

        return response()->json([
            self::KEY_MESSAGE => 'Analīze pabeigta',
            self::KEY_PAYLOAD => $result
        ], Response::HTTP_OK);
    }

    public function getMoves(int $id): JsonResponse
    {
        $moves = $this->moveRepo->getByGameId($id);

        return response()->json([
            self::KEY_MESSAGE => 'Gājieni ielādēti',
            self::KEY_PAYLOAD => [
                'moves' => GameMoveResource::collection($moves),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Save client-side (WASM) analysis results to the database.
     * The browser Stockfish analyzes moves, then POSTs results here for persistence.
     */
    public function saveMoves(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'moves' => 'required|array|min:1',
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

        // Clear previous analysis
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

        return response()->json([
            self::KEY_MESSAGE => 'Analīzes dati saglabāti',
            self::KEY_PAYLOAD => ['saved' => count($movesData), 'game_id' => $id]
        ], Response::HTTP_OK);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            self::KEY_MESSAGE => 'Statistika ielādēta',
            self::KEY_PAYLOAD => $this->gameService->getDashboardStats(),
        ], Response::HTTP_OK);
    }

    public function share(int $id): JsonResponse
    {
        $game = $this->gameRepo->findById($id);
        $token = $game->generateShareToken();

        return response()->json([
            self::KEY_MESSAGE => 'Kopīgošanas saite izveidota',
            self::KEY_PAYLOAD => ['share_url' => url("/shared/{$token}")]
        ], Response::HTTP_OK);
    }

    public function getShared(string $token): JsonResponse
    {
        $game = $this->gameRepo->findByShareToken($token);

        return response()->json([
            self::KEY_MESSAGE => 'Kopīgotā partija ielādēta',
            self::KEY_PAYLOAD => [
                self::KEY_GAME => new GameResource($game),
            ]
        ], Response::HTTP_OK);
    }

    /**
     * Download the PGN of a game as a .pgn file.
     */
    public function download(int $id, Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $game = $this->gameRepo->findById($id);

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
        // Avoid duplicating headers if they already exist in the stored PGN
        $pgn = str_contains($pgnBody, '[White ') ? $pgnBody : implode("\n", $headers) . "\n\n" . $pgnBody;

        return response($pgn, Response::HTTP_OK, [
            'Content-Type'        => 'application/x-chess-pgn',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
