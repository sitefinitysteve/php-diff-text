<?php

declare(strict_types=1);

namespace PhpDiffText;

/**
 * HTML-aware diff renderer with optional similarity threshold.
 *
 * When similarityThreshold is set and the texts are below that similarity,
 * renders a "full replacement" (entire old text as removed, entire new text as added).
 * Otherwise renders a word-level diff of the HTML content.
 */
final class DiffHtml
{
    /**
     * Render HTML diff output.
     *
     * @param array<string, mixed> $options
     */
    public static function render(
        string $oldText,
        string $newText,
        array $options = [],
        ?float $similarityThreshold = null,
    ): string {
        $isFullReplacement = self::isFullReplacement($oldText, $newText, $similarityThreshold);

        $html = '<div class="text-diff text-diff-html">';

        if ($isFullReplacement) {
            $html .= '<span class="diff-removed">' . $oldText . '</span>';
            $html .= '<span class="diff-added">' . $newText . '</span>';
        } else {
            $html .= self::renderWordDiff($oldText, $newText, $options);
        }

        $html .= '</div>';

        return $html;
    }

    private static function isFullReplacement(string $oldText, string $newText, ?float $similarityThreshold): bool
    {
        if ($similarityThreshold === null) {
            return false;
        }
        if ($oldText === '' || $newText === '') {
            return false;
        }

        $similarity = Similarity::compute($oldText, $newText);

        return $similarity < $similarityThreshold;
    }

    /**
     * Normalize input text: always normalize quotes, optionally strip formatting tags.
     *
     * @param array<string, mixed> $options
     */
    private static function normalizeInput(string $text, array $options): string
    {
        $text = NormalizeHtml::normalizeQuotes($text);

        if ($options['ignoreFormattingTags'] ?? false) {
            $text = NormalizeHtml::stripFormattingTags($text);
        }

        return $text;
    }

    /**
     * Render word-level diff, treating the input as HTML.
     *
     * @param array<string, mixed> $options
     */
    private static function renderWordDiff(string $oldText, string $newText, array $options): string
    {
        $ignoreCase = $options['ignoreCase'] ?? false;

        $normalizedOld = self::normalizeInput($oldText, $options);
        $normalizedNew = self::normalizeInput($newText, $options);

        $oldTokens = Tokenizer::wordsWithSpace($normalizedOld);
        $newTokens = Tokenizer::wordsWithSpace($normalizedNew);

        $changes = Diff::diffTokens($oldTokens, $newTokens, $ignoreCase);

        $result = '';
        foreach ($changes as $change) {
            if ($change->added) {
                $result .= '<span class="diff-added">' . $change->value . '</span>';
            } elseif ($change->removed) {
                $result .= '<span class="diff-removed">' . $change->value . '</span>';
            } else {
                $result .= $change->value;
            }
        }

        return $result;
    }
}
