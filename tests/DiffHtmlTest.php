<?php

declare(strict_types=1);

namespace PhpDiffText\Tests;

use PHPUnit\Framework\TestCase;
use PhpDiffText\DiffHtml;

final class DiffHtmlTest extends TestCase
{
    public function testRendersWordLevelDiffWithoutSimilarityThreshold(): void
    {
        $html = DiffHtml::render('Hello world', 'Hello Vue world');
        $this->assertStringContainsString('diff-added', $html);
    }

    public function testRendersFullReplacementWhenSimilarityBelowThreshold(): void
    {
        $html = DiffHtml::render(
            'It is expressly agreed and understood by the parties that the Husband shall maintain his existing life insurance policy.',
            'Item 1: House. Item 2: Car. Item 3: Savings.',
            [],
            0.3
        );
        $this->assertSame(1, substr_count($html, 'diff-removed'));
        $this->assertSame(1, substr_count($html, 'diff-added'));
        $this->assertStringContainsString('expressly agreed', $html);
        $this->assertStringContainsString('Item 1', $html);
    }

    public function testRendersWordLevelDiffWhenSimilarityAboveThreshold(): void
    {
        $html = DiffHtml::render(
            'The quick brown fox jumps over the lazy dog.',
            'The quick brown fox leaps over the lazy dog.',
            [],
            0.3
        );
        $this->assertStringContainsString('diff-added', $html);
        $isFullReplacement = substr_count($html, 'diff-removed') === 1
            && str_contains($html, '<span class="diff-removed">The quick brown fox');
        $this->assertFalse($isFullReplacement);
    }

    public function testDoesNotTriggerFullReplacementWhenThresholdIsNull(): void
    {
        $html = DiffHtml::render(
            'Completely different text here with many words.',
            'XYZ 123 ABC.'
        );
        $isFullReplacement = substr_count($html, 'diff-removed') === 1
            && substr_count($html, 'diff-added') === 1
            && str_contains($html, '>Completely different text here with many words.<')
            && str_contains($html, '>XYZ 123 ABC.<');
        $this->assertFalse($isFullReplacement);
    }

    public function testThresholdZeroNeverTriggersFullReplacement(): void
    {
        $html = DiffHtml::render(
            'Completely different text here.',
            'XYZ 123.',
            [],
            0.0
        );
        $isFullReplacement = substr_count($html, 'diff-removed') === 1
            && substr_count($html, 'diff-added') === 1
            && str_contains($html, '>Completely different text here.<')
            && str_contains($html, '>XYZ 123.<');
        $this->assertFalse($isFullReplacement);
    }

    public function testThresholdOneTriggersFullReplacementForNonIdenticalText(): void
    {
        $html = DiffHtml::render(
            'Hello world',
            'Hello worlds',
            [],
            1.0
        );
        $this->assertSame(1, substr_count($html, 'diff-removed'));
        $this->assertSame(1, substr_count($html, 'diff-added'));
        $this->assertStringContainsString('Hello world</span>', $html);
        $this->assertStringContainsString('Hello worlds</span>', $html);
    }

    public function testThresholdOneDoesNotTriggerForIdenticalText(): void
    {
        $html = DiffHtml::render(
            'Hello world',
            'Hello world',
            [],
            1.0
        );
        $this->assertStringNotContainsString('diff-removed', $html);
        $this->assertStringNotContainsString('diff-added', $html);
    }

    public function testIdenticalTextWithThresholdDoesNotTriggerFullReplacement(): void
    {
        $html = DiffHtml::render(
            'Same content here',
            'Same content here',
            [],
            0.3
        );
        $this->assertStringNotContainsString('diff-removed', $html);
        $this->assertStringNotContainsString('diff-added', $html);
    }

    public function testHandlesEmptyOldTextWithoutError(): void
    {
        $html = DiffHtml::render('', 'Some new text', [], 0.3);
        $this->assertNotEmpty($html);
        $this->assertStringNotContainsString('diff-removed', $html);
    }

    public function testHandlesEmptyNewTextWithoutError(): void
    {
        $html = DiffHtml::render('Some old text', '', [], 0.3);
        $this->assertNotEmpty($html);
    }

    public function testHandlesBothTextsEmptyWithThreshold(): void
    {
        $html = DiffHtml::render('', '', [], 0.3);
        $this->assertStringNotContainsString('diff-removed', $html);
        $this->assertStringNotContainsString('diff-added', $html);
    }
}
