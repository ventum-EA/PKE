<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class GameMoveResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'game_id' => $this->getGameId(),
            'move_number' => $this->getMoveNumber(),
            'color' => $this->getColor(),
            'move_san' => $this->getMoveSan(),
            'fen_before' => $this->getFenBefore(),
            'fen_after' => $this->getFenAfter(),
            'eval_before' => $this->getEvalBefore(),
            'eval_after' => $this->getEvalAfter(),
            'eval_diff' => $this->getEvalDiff(),
            'best_move' => $this->getBestMove(),
            'classification' => $this->getClassification(),
            'error_category' => $this->getErrorCategory(),
            'explanation' => $this->getExplanation(),
        ];
    }
}
