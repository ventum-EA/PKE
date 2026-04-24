<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameAnnotation;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnotationController extends Controller
{
    use ApiResponse;

    /**
     * Verify the user owns the game or it's shared with them.
     */
    private function authorizeGame(int $gameId, Request $request): void
    {
        $game = Game::findOrFail($gameId);
        if ($game->user_id !== $request->user()->id && !$game->share_token) {
            abort(403, 'Not authorized to annotate this game.');
        }
    }

    /**
     * GET /api/game/{id}/annotations — load all annotations for a game.
     */
    public function index(int $gameId, Request $request): JsonResponse
    {
        $annotations = GameAnnotation::where('game_id', $gameId)
            ->where('user_id', $request->user()->id)
            ->orderBy('move_index')
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'move_index' => $a->move_index,
                'comment'    => $a->comment,
                'arrows'     => $a->arrows ?? [],
                'highlights' => $a->highlights ?? [],
            ]);

        return $this->success('Annotations loaded.', [
            'annotations' => $annotations,
        ]);
    }

    /**
     * POST /api/game/{id}/annotations — save/update annotation for a move.
     */
    public function store(int $gameId, Request $request): JsonResponse
    {
        $this->authorizeGame($gameId, $request);

        $request->validate([
            'move_index' => 'required|integer|min:0',
            'comment'    => 'nullable|string|max:1000',
            'arrows'     => 'nullable|array|max:10',
            'arrows.*.from'  => 'required_with:arrows|string|size:2',
            'arrows.*.to'    => 'required_with:arrows|string|size:2',
            'arrows.*.color' => 'nullable|string|max:20',
            'highlights'           => 'nullable|array|max:16',
            'highlights.*.square'  => 'required_with:highlights|string|size:2',
            'highlights.*.color'   => 'nullable|string|max:20',
        ]);

        $annotation = GameAnnotation::updateOrCreate(
            [
                'game_id'    => $gameId,
                'user_id'    => $request->user()->id,
                'move_index' => $request->integer('move_index'),
            ],
            [
                'comment'    => $request->input('comment'),
                'arrows'     => $request->input('arrows', []),
                'highlights' => $request->input('highlights', []),
            ],
        );

        return $this->success('Annotation saved.', [
            'annotation' => [
                'id'         => $annotation->id,
                'move_index' => $annotation->move_index,
                'comment'    => $annotation->comment,
                'arrows'     => $annotation->arrows,
                'highlights' => $annotation->highlights,
            ],
        ]);
    }

    /**
     * DELETE /api/game/{id}/annotations/{moveIndex} — delete annotation.
     */
    public function destroy(int $gameId, int $moveIndex, Request $request): JsonResponse
    {
        $deleted = GameAnnotation::where('game_id', $gameId)
            ->where('user_id', $request->user()->id)
            ->where('move_index', $moveIndex)
            ->delete();

        return $this->success('Annotation deleted.', ['deleted' => $deleted > 0]);
    }
}
