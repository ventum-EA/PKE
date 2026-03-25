<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Request;

class GameResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getId(),
            'pgn' => $this->getPgn(),
            'white_player' => $this->getWhitePlayer(),
            'black_player' => $this->getBlackPlayer(),
            'result' => $this->getResult(),
            'opening_name' => $this->getOpeningName(),
            'opening_eco' => $this->getOpeningEco(),
            'total_moves' => $this->getTotalMoves(),
            'user_color' => $this->getUserColor(),
            'is_analyzed' => $this->getIsAnalyzed(),
            'share_token' => $this->getShareToken(),
            'played_at' => $this->getPlayedAt(),
            'created_at' => $this->getCreatedAt(),
            'user_id' => $this->getUserId(),
        ];
    }
}
