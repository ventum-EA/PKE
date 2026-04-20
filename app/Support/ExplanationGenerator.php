<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ErrorCategory;
use App\Enums\MoveClassification;

/**
 * ExplanationGenerator — produces human-readable explanations of move mistakes
 * in both Latvian (default) and English, based on the move's classification
 * (inaccuracy / mistake / blunder) and category (tactical / positional / opening / endgame).
 *
 * Best, excellent, and good moves return null — they do not need explaining.
 */
final class ExplanationGenerator
{
    /** Default locale used when no explicit locale is requested. */
    public const DEFAULT_LOCALE = 'lv';

    /** @var array<string, array<string, array<string, string>>> */
    private const TEMPLATES = [
        'lv' => [
            'tactical' => [
                'inaccuracy' => '{move} palaiž garām taktisku iespēju. Labāks gājiens: {best}',
                'mistake'    => 'Taktiska kļūda — {move} zaudē materiālu vai pozīciju. Labāk: {best}',
                'blunder'    => 'Smaga taktiska kļūda! {move} dod pretiniekam izšķirošu pārsvaru. Pareizi: {best}',
            ],
            'positional' => [
                'inaccuracy' => '{move} nedaudz pavājina pozīciju. Apsver: {best}',
                'mistake'    => 'Pozicionāla kļūda — {move} zaudē kontroli pār galvenajiem laukiem. Labāk: {best}',
                'blunder'    => 'Smaga pozicionāla kļūda! {move} kritiski sabojā pozīciju. Pareizi: {best}',
            ],
            'opening' => [
                'inaccuracy' => '{move} novirzās no labākās atklātņu teorijas. Apsver: {best}',
                'mistake'    => 'Atklātņu kļūda — {move} atpaliek attīstībā. Labāk: {best}',
                'blunder'    => 'Kritiska atklātņu kļūda! {move} rada tūlītējas problēmas. Pareizi: {best}',
            ],
            'endgame' => [
                'inaccuracy' => 'Galotnē {move} ir neprecīzs. Precīzāk: {best}',
                'mistake'    => 'Galotnes kļūda — {move} zaudē uzvarošu priekšrocību. Labāk: {best}',
                'blunder'    => 'Kritiska galotnes kļūda! {move} pārvērš uzvaru zaudējumā. Pareizi: {best}',
            ],
        ],
        'en' => [
            'tactical' => [
                'inaccuracy' => '{move} misses a tactical opportunity. Better: {best}',
                'mistake'    => 'Tactical error — {move} loses material or position. Better: {best}',
                'blunder'    => 'Serious tactical blunder! {move} gives the opponent a decisive advantage. Correct: {best}',
            ],
            'positional' => [
                'inaccuracy' => '{move} slightly weakens the position. Consider: {best}',
                'mistake'    => 'Positional mistake — {move} loses control of key squares. Better: {best}',
                'blunder'    => 'Major positional error! {move} critically damages the position. Correct: {best}',
            ],
            'opening' => [
                'inaccuracy' => '{move} deviates from the best opening theory line. Consider: {best}',
                'mistake'    => 'Opening error — {move} falls behind in development. Better: {best}',
                'blunder'    => 'Critical opening mistake! {move} creates immediate problems. Correct: {best}',
            ],
            'endgame' => [
                'inaccuracy' => 'In the endgame, {move} is imprecise. More accurate: {best}',
                'mistake'    => 'Endgame mistake — {move} loses a winning advantage. Better: {best}',
                'blunder'    => 'Critical endgame blunder! {move} turns a win into a loss. Correct: {best}',
            ],
        ],
    ];

    /** @var array<string, string> */
    private const FALLBACK = [
        'lv' => 'Labāks gājiens: {best}',
        'en' => 'Better move: {best}',
    ];

    /**
     * Generate a human-readable explanation of a move's classification.
     *
     * @param  MoveClassification    $classification  Move quality classification
     * @param  ErrorCategory|null    $category        Error category (tactical/positional/opening/endgame)
     * @param  string                $moveSan         The move in SAN notation (e.g. "Nf3")
     * @param  string|null           $bestMove        The engine's best move suggestion
     * @param  string                $locale          'lv' or 'en' (defaults to Latvian)
     * @return string|null  Explanation text, or null if the move doesn't need explaining
     */
    public static function generate(
        MoveClassification $classification,
        ?ErrorCategory $category,
        string $moveSan,
        ?string $bestMove,
        string $locale = self::DEFAULT_LOCALE,
    ): ?string {
        if (!ChessAnalyzer::isError($classification)) {
            return null;
        }

        if (!isset(self::TEMPLATES[$locale])) {
            $locale = self::DEFAULT_LOCALE;
        }

        $best = $bestMove !== null && $bestMove !== '' ? $bestMove : '?';
        $cat  = $category?->value ?? ErrorCategory::POSITIONAL->value;

        $template = self::TEMPLATES[$locale][$cat][$classification->value]
            ?? self::FALLBACK[$locale];

        return str_replace(['{move}', '{best}'], [$moveSan, $best], $template);
    }

    /**
     * Generate explanations in both locales at once — useful when storing
     * pre-rendered descriptions in the database for later display.
     *
     * @return array{lv: string|null, en: string|null}
     */
    public static function generateBoth(
        MoveClassification $classification,
        ?ErrorCategory $category,
        string $moveSan,
        ?string $bestMove,
    ): array {
        return [
            'lv' => self::generate($classification, $category, $moveSan, $bestMove, 'lv'),
            'en' => self::generate($classification, $category, $moveSan, $bestMove, 'en'),
        ];
    }
}
