<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserPattern;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Carbon;

class PatternDetectionService
{
    public function __construct(
        protected ConnectionInterface $db,
    ) {}

    /**
     * Analyze all of a user's games and detect recurring weakness patterns.
     * Returns newly detected patterns.
     */
    public function detect(int $userId): array
    {
        // Clear old patterns and regenerate
        UserPattern::where('user_id', $userId)->delete();

        $patterns = [];

        $patterns = array_merge($patterns, $this->detectHangingPieces($userId));
        $patterns = array_merge($patterns, $this->detectOpeningWeaknesses($userId));
        $patterns = array_merge($patterns, $this->detectTacticalBlindSpots($userId));
        $patterns = array_merge($patterns, $this->detectEndgameWeakness($userId));
        $patterns = array_merge($patterns, $this->detectBlunderPhases($userId));

        // Save detected patterns
        foreach ($patterns as $p) {
            UserPattern::create(array_merge($p, [
                'user_id'     => $userId,
                'detected_at' => now(),
            ]));
        }

        return $patterns;
    }

    /**
     * Get existing patterns for a user (without re-detecting).
     */
    public function getPatterns(int $userId): array
    {
        return UserPattern::where('user_id', $userId)
            ->orderByDesc('severity')
            ->orderByDesc('occurrences')
            ->get()
            ->toArray();
    }

    /**
     * Detect pieces that are frequently left undefended (tactical errors).
     */
    private function detectHangingPieces(int $userId): array
    {
        $blunders = $this->db->table('game_moves')
            ->join('games', 'games.id', '=', 'game_moves.game_id')
            ->where('games.user_id', $userId)
            ->where('games.is_analyzed', true)
            ->whereNull('games.deleted_at')
            ->where('game_moves.classification', 'blunder')
            ->where('game_moves.error_category', 'tactical')
            ->select('game_moves.game_id', 'game_moves.move_number', 'game_moves.fen_before', 'game_moves.move_san')
            ->get();

        if ($blunders->count() < 3) return [];

        $evidence = $blunders->take(5)->map(fn($b) => [
            'game_id' => $b->game_id,
            'move' => $b->move_number,
            'san' => $b->move_san,
        ])->toArray();

        return [[
            'pattern_type' => 'hanging_piece',
            'description' => "You've left pieces undefended in {$blunders->count()} positions across your analyzed games. This is your most frequent tactical error.",
            'description_lv' => "Tu esi atstājis figūras neaizsargātas {$blunders->count()} pozīcijās. Šī ir tava biežākā taktiskā kļūda.",
            'occurrences' => $blunders->count(),
            'severity' => min(3, intdiv($blunders->count(), 3) + 1),
            'suggestion' => 'Practice tactical puzzles focused on piece safety and double-attacks.',
            'suggestion_lv' => 'Trenējies ar taktiskiem uzdevumiem par figūru drošību un dubultuzbrukumiem.',
            'evidence' => $evidence,
        ]];
    }

    /**
     * Detect openings where the user consistently performs poorly.
     */
    private function detectOpeningWeaknesses(int $userId): array
    {
        $openings = $this->db->table('games')
            ->where('user_id', $userId)
            ->where('is_analyzed', true)
            ->whereNull('deleted_at')
            ->whereNotNull('opening_eco')
            ->selectRaw('opening_name, opening_eco, COUNT(*) as total,
                SUM(CASE WHEN (result = "1-0" AND user_color = "white") OR (result = "0-1" AND user_color = "black") THEN 1 ELSE 0 END) as wins')
            ->groupBy('opening_name', 'opening_eco')
            ->havingRaw('COUNT(*) >= 3')
            ->get();

        $patterns = [];
        foreach ($openings as $op) {
            $winRate = $op->total > 0 ? ($op->wins / $op->total) * 100 : 0;
            if ($winRate > 35) continue; // Only flag if win rate is bad

            $patterns[] = [
                'pattern_type' => 'opening_mistake',
                'description' => "You win only " . round($winRate) . "% with {$op->opening_name} ({$op->opening_eco}) across {$op->total} games.",
                'description_lv' => "Tu uzvari tikai " . round($winRate) . "% ar {$op->opening_name} ({$op->opening_eco}) {$op->total} partijās.",
                'occurrences' => $op->total - $op->wins,
                'severity' => $winRate < 20 ? 3 : 2,
                'suggestion' => "Study the key ideas in {$op->opening_name} or consider switching to a different opening.",
                'suggestion_lv' => "Izpēti {$op->opening_name} galvenās idejas vai apsver citu atklātni.",
                'evidence' => [['opening' => $op->opening_name, 'eco' => $op->opening_eco, 'games' => $op->total, 'wins' => $op->wins]],
            ];
        }

        return $patterns;
    }

    /**
     * Detect recurring tactical blind spots (missed forks, pins, etc.).
     */
    private function detectTacticalBlindSpots(int $userId): array
    {
        $categories = $this->db->table('game_moves')
            ->join('games', 'games.id', '=', 'game_moves.game_id')
            ->where('games.user_id', $userId)
            ->where('games.is_analyzed', true)
            ->whereNull('games.deleted_at')
            ->whereIn('game_moves.classification', ['mistake', 'blunder'])
            ->whereNotNull('game_moves.error_category')
            ->selectRaw('game_moves.error_category, COUNT(*) as count')
            ->groupBy('game_moves.error_category')
            ->orderByDesc('count')
            ->get();

        $patterns = [];
        foreach ($categories as $cat) {
            if ($cat->count < 3) continue;

            $labels = [
                'tactical' => ['Tactical errors', 'Taktiskās kļūdas'],
                'positional' => ['Positional errors', 'Pozicionālās kļūdas'],
                'opening' => ['Opening errors', 'Atklātnes kļūdas'],
                'endgame' => ['Endgame errors', 'Galotnes kļūdas'],
            ];

            $label = $labels[$cat->error_category] ?? [$cat->error_category, $cat->error_category];

            $patterns[] = [
                'pattern_type' => 'category_weakness',
                'description' => "{$label[0]}: {$cat->count} mistakes across your games. This is a consistent area for improvement.",
                'description_lv' => "{$label[1]}: {$cat->count} kļūdas tavās partijās. Šī ir konsekventa uzlabojuma joma.",
                'occurrences' => $cat->count,
                'severity' => min(3, intdiv($cat->count, 5) + 1),
                'suggestion' => "Focus on {$label[0]} training puzzles to reduce these errors.",
                'suggestion_lv' => "Fokusējies uz {$label[1]} treniņu uzdevumiem.",
                'evidence' => [['category' => $cat->error_category, 'count' => $cat->count]],
            ];
        }

        return $patterns;
    }

    /**
     * Detect if the user is weak in endgames specifically.
     */
    private function detectEndgameWeakness(int $userId): array
    {
        $endgameErrors = $this->db->table('game_moves')
            ->join('games', 'games.id', '=', 'game_moves.game_id')
            ->where('games.user_id', $userId)
            ->where('games.is_analyzed', true)
            ->whereNull('games.deleted_at')
            ->where('game_moves.error_category', 'endgame')
            ->whereIn('game_moves.classification', ['mistake', 'blunder'])
            ->count();

        if ($endgameErrors < 4) return [];

        return [[
            'pattern_type' => 'weak_endgame',
            'description' => "You've made {$endgameErrors} significant endgame errors. Endgame technique is an area where focused study would improve your results.",
            'description_lv' => "Tu esi pieļāvis {$endgameErrors} nozīmīgas galotnes kļūdas. Galotnes tehnika ir joma, kur fokusēta mācīšanās uzlabotu tavus rezultātus.",
            'occurrences' => $endgameErrors,
            'severity' => min(3, intdiv($endgameErrors, 4) + 1),
            'suggestion' => 'Practice basic endgames: king + pawn, rook endgames, and basic checkmate patterns.',
            'suggestion_lv' => 'Trenējies pamata galotnēs: karalis + bandinieks, torņu galotnes un pamata mata shēmas.',
            'evidence' => [],
        ]];
    }

    /**
     * Detect which game phase (early/mid/late) the user makes the most mistakes.
     */
    private function detectBlunderPhases(int $userId): array
    {
        $phases = $this->db->table('game_moves')
            ->join('games', 'games.id', '=', 'game_moves.game_id')
            ->where('games.user_id', $userId)
            ->where('games.is_analyzed', true)
            ->whereNull('games.deleted_at')
            ->whereIn('game_moves.classification', ['mistake', 'blunder'])
            ->selectRaw('
                CASE
                    WHEN game_moves.move_number <= 10 THEN "opening"
                    WHEN game_moves.move_number <= 25 THEN "middlegame"
                    ELSE "endgame"
                END as phase,
                COUNT(*) as count
            ')
            ->groupBy('phase')
            ->orderByDesc('count')
            ->get();

        if ($phases->isEmpty() || $phases->max('count') < 3) return [];

        $worst = $phases->first();
        $phaseLabels = [
            'opening' => ['Opening phase (moves 1-10)', 'Atklātnes fāze (gājieni 1-10)'],
            'middlegame' => ['Middlegame phase (moves 11-25)', 'Viduspēles fāze (gājieni 11-25)'],
            'endgame' => ['Endgame phase (moves 26+)', 'Galotnes fāze (gājieni 26+)'],
        ];
        $label = $phaseLabels[$worst->phase] ?? [$worst->phase, $worst->phase];

        return [[
            'pattern_type' => 'blunder_phase',
            'description' => "Most of your mistakes ({$worst->count}) occur in the {$label[0]}. This is your weakest game phase.",
            'description_lv' => "Lielākā daļa tavu kļūdu ({$worst->count}) notiek {$label[1]}. Šī ir tava vājākā spēles fāze.",
            'occurrences' => $worst->count,
            'severity' => 2,
            'suggestion' => "Study {$label[0]} strategies and practice positions from this phase.",
            'suggestion_lv' => "Studē {$label[1]} stratēģijas un trenējies ar šīs fāzes pozīcijām.",
            'evidence' => $phases->toArray(),
        ]];
    }
}
