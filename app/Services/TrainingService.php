<?php

namespace App\Services;

use App\Repositories\GameMoveRepository;
use App\Repositories\TrainingSessionRepository;
use Illuminate\Contracts\Auth\Guard;

class TrainingService
{
    public function __construct(
        protected TrainingSessionRepository $sessionRepo,
        protected GameMoveRepository $moveRepo,
        protected Guard $auth
    ) {}

    public function generatePuzzleFromErrors(int $gameId): array
    {
        $errors = $this->moveRepo->getErrorsByGameId($gameId);

        if ($errors->isEmpty()) {
            return ['puzzles' => [], 'message' => 'Nav kļūdu, no kurām ģenerēt uzdevumus.'];
        }

        $puzzles = [];
        foreach ($errors->take(5) as $error) {
            $fenBefore = $error->getFenBefore();
            $bestMove = $error->getBestMove();

            // Skip if we don't have a valid FEN or best move
            if (!$fenBefore || $fenBefore === 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1') {
                // Only skip if this is clearly the starting position AND move_number > 1
                if ($error->getMoveNumber() > 1) continue;
            }
            if (!$bestMove) continue;

            // Normalize the best move: store BOTH SAN and UCI forms
            // so we can match either format from the frontend
            $bestMoveSan = $error->getBestMove();
            $bestMoveUci = $this->sanToApproxUci($bestMoveSan);

            $session = $this->sessionRepo->store([
                'user_id' => $this->auth->id(),
                'game_id' => $gameId,
                'fen' => $fenBefore,
                'correct_move' => $bestMoveUci ?: $bestMoveSan, // Store UCI for comparison
                'category' => $error->getErrorCategory() ?? 'tactical',
                'hint' => $error->getExplanation(),
            ]);

            $puzzles[] = [
                'id' => $session->getId(),
                'fen' => $session->getFen(),
                'category' => $session->getCategory(),
                'hint' => $session->getHint(),
                'move_number' => $error->getMoveNumber(),
                'correct_san' => $bestMoveSan,
            ];
        }

        return ['puzzles' => $puzzles];
    }

    public function submitAnswer(int $sessionId, string $userMove): array
    {
        $session = $this->sessionRepo->findById($sessionId);
        $correctMove = strtolower(trim($session->getCorrectMove()));
        $userMoveClean = strtolower(trim($userMove));

        // Match either UCI (e2e4) or the stored form
        // Also handle UCI with/without promotion suffix
        $isCorrect = $userMoveClean === $correctMove
            || substr($userMoveClean, 0, 4) === substr($correctMove, 0, 4);

        $this->sessionRepo->update($session, [
            'user_move' => $userMove,
            'is_correct' => $isCorrect,
        ]);

        return [
            'is_correct' => $isCorrect,
            'correct_move' => $session->getCorrectMove(),
            'hint' => $session->getHint(),
        ];
    }

    /**
     * Approximate SAN to UCI conversion.
     * This is a best-effort conversion since we don't have the board state.
     * The frontend sends UCI (e2e4), so we try to store UCI.
     */
    private function sanToApproxUci(?string $san): ?string
    {
        if (!$san) return null;
        // If it already looks like UCI (4-5 chars, all lowercase letters/digits), keep it
        if (preg_match('/^[a-h][1-8][a-h][1-8][qrbn]?$/', $san)) {
            return $san;
        }
        // Otherwise return null — comparison will use the SAN form
        return null;
    }

    public function getProgress(): array
    {
        return $this->sessionRepo->getUserProgress($this->auth->id());
    }
}
