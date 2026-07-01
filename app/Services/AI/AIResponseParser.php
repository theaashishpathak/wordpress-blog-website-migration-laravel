<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Exceptions\AIProviderException;

/**
 * Utility for extracting structured data from LLM responses.
 *
 * Models often wrap JSON in markdown code fences, prepend an explanatory
 * sentence, or trail with closing commentary even when explicitly told
 * not to. This parser is forgiving:
 *
 *   1. Strip ```json … ``` and ``` … ``` fences if present.
 *   2. Find the first {...} or [...] block via balanced-brace scan.
 *   3. json_decode that block.
 *
 * Throws AIProviderException on irrecoverable garbage so the caller can
 * decide whether to retry or surface a friendly UI error.
 */
final class AIResponseParser
{
    /**
     * Extract a JSON object/array from $content. Returns the decoded
     * value (associative array).
     *
     * @return array<int|string, mixed>
     */
    public static function extractJson(string $content, ?string $providerName = null): array
    {
        $stripped = self::stripCodeFences($content);
        $block = self::findFirstJsonBlock($stripped);

        if ($block === null) {
            throw new AIProviderException(
                message: 'AI response did not contain any JSON. Raw: '
                    .self::truncateForError($content),
                providerName: $providerName,
            );
        }

        $decoded = json_decode($block, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw new AIProviderException(
                message: 'AI response JSON could not be parsed: '
                    .json_last_error_msg()
                    .'. Block: '.self::truncateForError($block),
                providerName: $providerName,
            );
        }

        return $decoded;
    }

    /**
     * Strip triple-backtick code fences (with or without a language tag).
     */
    private static function stripCodeFences(string $content): string
    {
        $content = trim($content);

        // ```json\n...\n```  OR  ```\n...\n```
        if (preg_match('/```(?:json|JSON)?\s*\n?([\s\S]*?)\n?```/m', $content, $matches) === 1) {
            return trim($matches[1]);
        }

        return $content;
    }

    /**
     * Locate the first balanced {...} or [...] block in the string.
     * Returns null when no balanced block exists.
     */
    private static function findFirstJsonBlock(string $content): ?string
    {
        $length = strlen($content);
        $openChar = null;
        $closeChar = null;
        $start = -1;

        for ($i = 0; $i < $length; $i++) {
            $char = $content[$i];

            if ($char === '{' || $char === '[') {
                $openChar = $char;
                $closeChar = $char === '{' ? '}' : ']';
                $start = $i;
                break;
            }
        }

        if ($start === -1 || $openChar === null) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escape = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $content[$i];

            if ($escape) {
                $escape = false;

                continue;
            }

            if ($char === '\\') {
                $escape = true;

                continue;
            }

            if ($char === '"') {
                $inString = ! $inString;

                continue;
            }

            if ($inString) {
                continue;
            }

            if ($char === $openChar) {
                $depth++;
            } elseif ($char === $closeChar) {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    private static function truncateForError(string $value, int $max = 200): string
    {
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max).'…';
    }
}
