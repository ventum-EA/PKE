<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GameRepository;
use Illuminate\Contracts\Auth\Guard;

/**
 * Generates personalized training recommendations for the authenticated user,
 * based on their error statistics and opening performance.
 *
 * Fulfils spec §2.2.6 (Personalizētu ieteikumu izveide) and §2.2.15
 * (Personalizēta atklātņu treniņu sesija).
 */
class RecommendationService
{
    public function __construct(
        protected GameRepository $gameRepo,
        protected Guard $auth
    ) {}

    /**
     * @return array{recommendations: array<int,array>, summary: array}
     */
    public function generate(): array
    {
        $userId = $this->auth->id();

        $errorStats   = $this->gameRepo->getErrorStats($userId);
        $openingStats = $this->gameRepo->getOpeningStats($userId);
        $playerStats  = $this->gameRepo->getPlayerStats($userId);

        $errorsByCategory = $this->aggregateByCategory($errorStats);
        $recommendations  = [];

        // 1. Category-based recommendations (tactical / positional / opening / endgame)
        foreach ($errorsByCategory as $category => $totals) {
            $severity = $totals['blunder'] * 3 + $totals['mistake'] * 2 + $totals['inaccuracy'];
            if ($severity < 3) continue; // not enough signal

            $recommendations[] = $this->buildCategoryRecommendation($category, $totals, $severity);
        }

        // 2. Worst-performing opening recommendation (§2.2.15)
        $worstOpening = $this->findWorstOpening($openingStats);
        if ($worstOpening) {
            $recommendations[] = [
                'type'        => 'opening_review',
                'category'    => 'opening',
                'priority'    => 'medium',
                'title'       => 'Pārskati savu vājāko atklātni',
                'message'     => sprintf(
                    '“%s” (%s) — %d partijas, uzvaras: %d%%. Apskati piemērus un iemācies galvenos principus.',
                    $worstOpening['opening_name'],
                    $worstOpening['opening_eco'] ?? '—',
                    $worstOpening['total'],
                    $worstOpening['win_rate'],
                ),
                'action_url'  => '/openings',
                'data'        => $worstOpening,
            ];
        }

        // 3. General activity recommendation if the user hasn't played much
        if (($playerStats['total_games'] ?? 0) < 5) {
            $recommendations[] = [
                'type'       => 'play_more',
                'category'   => 'general',
                'priority'   => 'low',
                'title'      => 'Nospēlē vairāk partiju',
                'message'    => 'Lai sistēma varētu sniegt precīzus ieteikumus, nospēlē vismaz 5 partijas.',
                'action_url' => '/play',
                'data'       => ['played' => $playerStats['total_games'] ?? 0],
            ];
        }

        // Sort by priority (high > medium > low)
        $priorityRank = ['high' => 0, 'medium' => 1, 'low' => 2];
        usort($recommendations, fn($a, $b) => $priorityRank[$a['priority']] <=> $priorityRank[$b['priority']]);

        return [
            'recommendations' => $recommendations,
            'summary'         => [
                'total_errors'       => array_sum(array_map(fn($c) => $c['blunder'] + $c['mistake'] + $c['inaccuracy'], $errorsByCategory)),
                'errors_by_category' => $errorsByCategory,
                'worst_opening'      => $worstOpening,
            ],
        ];
    }

    /**
     * Collapse the flat rows from GameRepository::getErrorStats() into a
     * per-category structure: [category => [blunder, mistake, inaccuracy]].
     */
    private function aggregateByCategory(array $errorStats): array
    {
        $out = [];
        foreach ($errorStats as $row) {
            $cat = (array) $row;
            $category = $cat['error_category'] ?? 'positional';
            $classification = $cat['classification'] ?? 'inaccuracy';
            $total = (int) ($cat['total'] ?? 0);

            if (!isset($out[$category])) {
                $out[$category] = ['blunder' => 0, 'mistake' => 0, 'inaccuracy' => 0];
            }
            if (isset($out[$category][$classification])) {
                $out[$category][$classification] += $total;
            }
        }
        return $out;
    }

    private function buildCategoryRecommendation(string $category, array $totals, int $severity): array
    {
        $priority = $severity >= 10 ? 'high' : ($severity >= 5 ? 'medium' : 'low');

        $templates = [
            'tactical' => [
                'title'      => 'Trenē taktiskos uzdevumus',
                'message'    => sprintf('Tu esi pieļāvis %d taktisko kļūdu (%d blunderi). Atrisini mate-in-1 un viltības uzdevumus.', $totals['blunder'] + $totals['mistake'] + $totals['inaccuracy'], $totals['blunder']),
                'action_url' => '/puzzles',
            ],
            'positional' => [
                'title'      => 'Uzlabo pozicionālo izpratni',
                'message'    => sprintf('Pozicionālās kļūdas: %d. Strādā ar lauku kontroli un figūru koordināciju.', $totals['blunder'] + $totals['mistake'] + $totals['inaccuracy']),
                'action_url' => '/lessons',
            ],
            'opening' => [
                'title'      => 'Atkārto atklātnes teoriju',
                'message'    => sprintf('Atklātnes kļūdas: %d. Apskati atklātnes, kuras spēlē, un to galvenos plānus.', $totals['blunder'] + $totals['mistake'] + $totals['inaccuracy']),
                'action_url' => '/openings',
            ],
            'endgame' => [
                'title'      => 'Trenē galotnes',
                'message'    => sprintf('Galotnes kļūdas: %d. Apgūsti pamata mattēšanas tehnikas un bandinieku galotnes.', $totals['blunder'] + $totals['mistake'] + $totals['inaccuracy']),
                'action_url' => '/training',
            ],
        ];

        $tpl = $templates[$category] ?? $templates['positional'];

        return [
            'type'       => $category . '_training',
            'category'   => $category,
            'priority'   => $priority,
            'title'      => $tpl['title'],
            'message'    => $tpl['message'],
            'action_url' => $tpl['action_url'],
            'data'       => $totals + ['severity' => $severity],
        ];
    }

    /**
     * Pick the opening with the lowest win rate (minimum 3 games played).
     */
    private function findWorstOpening(array $openingStats): ?array
    {
        $worst = null;
        foreach ($openingStats as $row) {
            $arr = (array) $row;
            $total = (int) ($arr['total'] ?? 0);
            if ($total < 3) continue;

            $wins    = (int) ($arr['wins'] ?? 0);
            $winRate = $total > 0 ? round(($wins / $total) * 100, 1) : 0;

            if ($worst === null || $winRate < $worst['win_rate']) {
                $worst = [
                    'opening_name' => $arr['opening_name'] ?? 'Nezināma',
                    'opening_eco'  => $arr['opening_eco'] ?? null,
                    'total'        => $total,
                    'wins'         => $wins,
                    'win_rate'     => $winRate,
                ];
            }
        }
        return $worst;
    }
}
