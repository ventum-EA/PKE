<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\ErrorCategory;
use App\Enums\MoveClassification;
use App\Support\ExplanationGenerator;
use Tests\TestCase;

final class ExplanationGeneratorTest extends TestCase
{
    public function test_returns_null_for_best_moves(): void
    {
        $explanation = ExplanationGenerator::generate(
            MoveClassification::BEST,
            ErrorCategory::TACTICAL,
            'Nf3',
            'Nf3',
        );

        $this->assertNull($explanation);
    }

    public function test_returns_null_for_excellent_moves(): void
    {
        $explanation = ExplanationGenerator::generate(
            MoveClassification::EXCELLENT,
            ErrorCategory::POSITIONAL,
            'e4',
            'e4',
        );

        $this->assertNull($explanation);
    }

    public function test_generates_latvian_blunder_explanation_by_default(): void
    {
        $explanation = ExplanationGenerator::generate(
            MoveClassification::BLUNDER,
            ErrorCategory::TACTICAL,
            'Qxh7??',
            'Nxd5',
        );

        $this->assertNotNull($explanation);
        // Latvian-specific phrasing
        $this->assertStringContainsString('taktiska', strtolower($explanation));
        $this->assertStringContainsString('Qxh7??', $explanation);
        $this->assertStringContainsString('Nxd5', $explanation);
    }

    public function test_generates_english_explanation_when_locale_is_en(): void
    {
        $explanation = ExplanationGenerator::generate(
            MoveClassification::BLUNDER,
            ErrorCategory::TACTICAL,
            'Qxh7??',
            'Nxd5',
            'en',
        );

        $this->assertNotNull($explanation);
        $this->assertStringContainsString('tactical', strtolower($explanation));
        $this->assertStringContainsString('Qxh7??', $explanation);
    }

    public function test_falls_back_to_default_locale_for_unknown_locale(): void
    {
        // Asking for 'ru' or any unknown locale should fall back to lv (default)
        $explanation = ExplanationGenerator::generate(
            MoveClassification::MISTAKE,
            ErrorCategory::OPENING,
            'h6?',
            'Nc6',
            'ru',
        );

        $this->assertNotNull($explanation);
        // Should be Latvian content
        $this->assertStringContainsString('Atklātņu', $explanation);
    }

    public function test_handles_missing_category_gracefully(): void
    {
        $explanation = ExplanationGenerator::generate(
            MoveClassification::MISTAKE,
            null,
            'h6?',
            'Nc6',
        );

        // Should fall back to positional templates when category is unknown
        $this->assertNotNull($explanation);
        $this->assertStringContainsString('h6?', $explanation);
        $this->assertStringContainsString('Nc6', $explanation);
    }

    public function test_handles_missing_best_move(): void
    {
        $explanation = ExplanationGenerator::generate(
            MoveClassification::BLUNDER,
            ErrorCategory::TACTICAL,
            'Qxh7??',
            null,
        );

        $this->assertNotNull($explanation);
        // Should substitute ? for unknown best move, never crash
        $this->assertStringContainsString('?', $explanation);
    }

    public function test_generate_both_returns_both_locales(): void
    {
        $both = ExplanationGenerator::generateBoth(
            MoveClassification::MISTAKE,
            ErrorCategory::ENDGAME,
            'Kg1',
            'Kf2',
        );

        $this->assertArrayHasKey('lv', $both);
        $this->assertArrayHasKey('en', $both);
        $this->assertNotNull($both['lv']);
        $this->assertNotNull($both['en']);
        $this->assertNotEquals($both['lv'], $both['en']);
        $this->assertStringContainsString('Galotnes', $both['lv']);
        $this->assertStringContainsString('Endgame', $both['en']);
    }

    public function test_generate_both_returns_nulls_for_best_moves(): void
    {
        $both = ExplanationGenerator::generateBoth(
            MoveClassification::BEST,
            ErrorCategory::POSITIONAL,
            'Nf3',
            'Nf3',
        );

        $this->assertNull($both['lv']);
        $this->assertNull($both['en']);
    }
}
