<?php

declare(strict_types=1);

namespace App\Services\Seo;

use App\Services\Seo\DataTransferObjects\SeoCheckResult;
use App\Services\Seo\DataTransferObjects\SeoScoreInput;
use App\Services\Seo\DataTransferObjects\SeoScoreResult;

/**
 * Heuristic SEO scorer modelled loosely on Yoast / Rank Math.
 *
 * Stateless and pure — every call runs the full check battery against
 * the supplied input. Total weights sum to 100 so the overall score is
 * a clean 0-100 scale.
 *
 * Weight table (must sum to 100):
 *   metaTitleLength            15
 *   metaDescriptionLength      15
 *   focusKeywordPresent         5
 *   focusKeywordInTitle        12
 *   focusKeywordInSlug          8
 *   focusKeywordInIntro        10
 *   keywordDensity             10
 *   contentLength              15
 *   readability                10
 *                              ===
 *                              100
 */
class SeoScoreService
{
    private const META_TITLE_MIN = 30;

    private const META_TITLE_IDEAL_MIN = 50;

    private const META_TITLE_IDEAL_MAX = 60;

    private const META_DESCRIPTION_MIN = 70;

    private const META_DESCRIPTION_IDEAL_MIN = 120;

    private const META_DESCRIPTION_IDEAL_MAX = 160;

    private const CONTENT_MIN_WORDS = 150;

    private const CONTENT_IDEAL_WORDS = 300;

    private const INTRO_WORD_WINDOW = 100;

    private const KEYWORD_DENSITY_IDEAL_MIN = 0.5;     // %

    private const KEYWORD_DENSITY_IDEAL_MAX = 3.0;     // %

    private const KEYWORD_DENSITY_HARD_MAX = 5.0;      // % — flagged as stuffing

    private const FLESCH_GOOD_MIN = 60.0;

    private const FLESCH_WARNING_MIN = 30.0;

    public function score(SeoScoreInput $input): SeoScoreResult
    {
        $checks = [
            $this->checkMetaTitleLength($input->metaTitle, $input->title),
            $this->checkMetaDescriptionLength($input->metaDescription, $input->excerpt),
            $this->checkFocusKeywordPresent($input->focusKeyword),
            $this->checkFocusKeywordInTitle($input->focusKeyword, $input->title),
            $this->checkFocusKeywordInSlug($input->focusKeyword, $input->slug),
            $this->checkFocusKeywordInIntro($input->focusKeyword, $input->content),
            $this->checkKeywordDensity($input->focusKeyword, $input->content),
            $this->checkContentLength($input->content),
            $this->checkReadability($input->content),
        ];

        $overall = (int) round(array_sum(array_map(fn (SeoCheckResult $c): float => $c->score(), $checks)));

        return new SeoScoreResult(
            overall: max(0, min(100, $overall)),
            checks: $checks,
        );
    }

    // -------------------------------------------------------------------------
    // Individual checks
    // -------------------------------------------------------------------------

    private function checkMetaTitleLength(string $metaTitle, string $fallbackTitle): SeoCheckResult
    {
        $effective = $metaTitle !== '' ? $metaTitle : $fallbackTitle;
        $len = mb_strlen($effective);

        if ($len === 0) {
            return new SeoCheckResult(
                key: 'meta_title_length',
                label: 'Meta title length',
                level: 'bad',
                message: 'Add a meta title (50-60 chars ideal).',
                weight: 15,
            );
        }

        if ($len >= self::META_TITLE_IDEAL_MIN && $len <= self::META_TITLE_IDEAL_MAX) {
            return new SeoCheckResult(
                key: 'meta_title_length',
                label: 'Meta title length',
                level: 'good',
                message: "Meta title length is ideal ({$len} chars).",
                weight: 15,
            );
        }

        if ($len >= self::META_TITLE_MIN && $len <= 70) {
            return new SeoCheckResult(
                key: 'meta_title_length',
                label: 'Meta title length',
                level: 'warning',
                message: "Meta title is {$len} chars — aim for 50-60 for best SERP fit.",
                weight: 15,
            );
        }

        return new SeoCheckResult(
            key: 'meta_title_length',
            label: 'Meta title length',
            level: 'bad',
            message: $len < self::META_TITLE_MIN
                ? "Meta title is too short ({$len} chars). Aim for 50-60."
                : "Meta title is too long ({$len} chars). Trim to 50-60.",
            weight: 15,
        );
    }

    private function checkMetaDescriptionLength(string $metaDescription, string $fallbackExcerpt): SeoCheckResult
    {
        $effective = $metaDescription !== '' ? $metaDescription : $fallbackExcerpt;
        $len = mb_strlen($effective);

        if ($len === 0) {
            return new SeoCheckResult(
                key: 'meta_description_length',
                label: 'Meta description length',
                level: 'bad',
                message: 'Add a meta description (120-160 chars ideal).',
                weight: 15,
            );
        }

        if ($len >= self::META_DESCRIPTION_IDEAL_MIN && $len <= self::META_DESCRIPTION_IDEAL_MAX) {
            return new SeoCheckResult(
                key: 'meta_description_length',
                label: 'Meta description length',
                level: 'good',
                message: "Meta description is well-sized ({$len} chars).",
                weight: 15,
            );
        }

        if ($len >= self::META_DESCRIPTION_MIN && $len <= 180) {
            return new SeoCheckResult(
                key: 'meta_description_length',
                label: 'Meta description length',
                level: 'warning',
                message: "Meta description is {$len} chars — aim for 120-160.",
                weight: 15,
            );
        }

        return new SeoCheckResult(
            key: 'meta_description_length',
            label: 'Meta description length',
            level: 'bad',
            message: $len < self::META_DESCRIPTION_MIN
                ? "Meta description is too short ({$len} chars)."
                : "Meta description is too long ({$len} chars).",
            weight: 15,
        );
    }

    private function checkFocusKeywordPresent(string $focusKeyword): SeoCheckResult
    {
        if (trim($focusKeyword) === '') {
            return new SeoCheckResult(
                key: 'focus_keyword_present',
                label: 'Focus keyword set',
                level: 'bad',
                message: 'Set a focus keyword so we can score keyword placement.',
                weight: 5,
            );
        }

        return new SeoCheckResult(
            key: 'focus_keyword_present',
            label: 'Focus keyword set',
            level: 'good',
            message: 'Focus keyword is set.',
            weight: 5,
        );
    }

    private function checkFocusKeywordInTitle(string $focusKeyword, string $title): SeoCheckResult
    {
        if (trim($focusKeyword) === '' || trim($title) === '') {
            return new SeoCheckResult(
                key: 'focus_keyword_in_title',
                label: 'Focus keyword in title',
                level: 'bad',
                message: 'Include the focus keyword in the title.',
                weight: 12,
            );
        }

        return $this->containsCaseInsensitive($title, $focusKeyword)
            ? new SeoCheckResult(
                key: 'focus_keyword_in_title',
                label: 'Focus keyword in title',
                level: 'good',
                message: 'Focus keyword appears in the title.',
                weight: 12,
            )
            : new SeoCheckResult(
                key: 'focus_keyword_in_title',
                label: 'Focus keyword in title',
                level: 'bad',
                message: 'Focus keyword is missing from the title.',
                weight: 12,
            );
    }

    private function checkFocusKeywordInSlug(string $focusKeyword, string $slug): SeoCheckResult
    {
        if (trim($focusKeyword) === '' || trim($slug) === '') {
            return new SeoCheckResult(
                key: 'focus_keyword_in_slug',
                label: 'Focus keyword in slug',
                level: 'bad',
                message: 'Include the focus keyword in the slug.',
                weight: 8,
            );
        }

        $slugKeyword = str_replace(' ', '-', strtolower(trim($focusKeyword)));

        return str_contains(strtolower($slug), $slugKeyword)
            ? new SeoCheckResult(
                key: 'focus_keyword_in_slug',
                label: 'Focus keyword in slug',
                level: 'good',
                message: 'Slug contains the focus keyword.',
                weight: 8,
            )
            : new SeoCheckResult(
                key: 'focus_keyword_in_slug',
                label: 'Focus keyword in slug',
                level: 'warning',
                message: 'Slug does not contain the focus keyword.',
                weight: 8,
            );
    }

    private function checkFocusKeywordInIntro(string $focusKeyword, string $content): SeoCheckResult
    {
        if (trim($focusKeyword) === '') {
            return new SeoCheckResult(
                key: 'focus_keyword_in_intro',
                label: 'Focus keyword in intro',
                level: 'bad',
                message: 'Mention the focus keyword in the first paragraph.',
                weight: 10,
            );
        }

        $plain = $this->stripHtml($content);
        $intro = $this->firstNWords($plain, self::INTRO_WORD_WINDOW);

        if ($intro === '') {
            return new SeoCheckResult(
                key: 'focus_keyword_in_intro',
                label: 'Focus keyword in intro',
                level: 'bad',
                message: 'Write an intro paragraph that mentions the focus keyword.',
                weight: 10,
            );
        }

        return $this->containsCaseInsensitive($intro, $focusKeyword)
            ? new SeoCheckResult(
                key: 'focus_keyword_in_intro',
                label: 'Focus keyword in intro',
                level: 'good',
                message: 'Focus keyword appears in the first 100 words.',
                weight: 10,
            )
            : new SeoCheckResult(
                key: 'focus_keyword_in_intro',
                label: 'Focus keyword in intro',
                level: 'warning',
                message: 'Mention the focus keyword in the first paragraph.',
                weight: 10,
            );
    }

    private function checkKeywordDensity(string $focusKeyword, string $content): SeoCheckResult
    {
        $plain = $this->stripHtml($content);
        $totalWords = $this->wordCount($plain);

        if (trim($focusKeyword) === '' || $totalWords < 30) {
            return new SeoCheckResult(
                key: 'keyword_density',
                label: 'Keyword density',
                level: 'bad',
                message: 'Add more content before density can be measured.',
                weight: 10,
            );
        }

        $density = $this->keywordDensityPercent($plain, $focusKeyword, $totalWords);
        $formatted = number_format($density, 2);

        if ($density >= self::KEYWORD_DENSITY_IDEAL_MIN && $density <= self::KEYWORD_DENSITY_IDEAL_MAX) {
            return new SeoCheckResult(
                key: 'keyword_density',
                label: 'Keyword density',
                level: 'good',
                message: "Keyword density is healthy ({$formatted}%).",
                weight: 10,
            );
        }

        if ($density > self::KEYWORD_DENSITY_HARD_MAX) {
            return new SeoCheckResult(
                key: 'keyword_density',
                label: 'Keyword density',
                level: 'bad',
                message: "Keyword stuffing detected ({$formatted}%). Reduce repetitions.",
                weight: 10,
            );
        }

        return new SeoCheckResult(
            key: 'keyword_density',
            label: 'Keyword density',
            level: 'warning',
            message: $density < self::KEYWORD_DENSITY_IDEAL_MIN
                ? "Keyword density is low ({$formatted}%). Mention the keyword a few more times."
                : "Keyword density is {$formatted}% — keep it under 3%.",
            weight: 10,
        );
    }

    private function checkContentLength(string $content): SeoCheckResult
    {
        $wordCount = $this->wordCount($this->stripHtml($content));

        if ($wordCount >= self::CONTENT_IDEAL_WORDS) {
            return new SeoCheckResult(
                key: 'content_length',
                label: 'Content length',
                level: 'good',
                message: "Content is {$wordCount} words — solid depth.",
                weight: 15,
            );
        }

        if ($wordCount >= self::CONTENT_MIN_WORDS) {
            return new SeoCheckResult(
                key: 'content_length',
                label: 'Content length',
                level: 'warning',
                message: "Content is {$wordCount} words — aim for 300+ for stronger SEO.",
                weight: 15,
            );
        }

        return new SeoCheckResult(
            key: 'content_length',
            label: 'Content length',
            level: 'bad',
            message: "Content is only {$wordCount} words. Long-form content ranks better.",
            weight: 15,
        );
    }

    private function checkReadability(string $content): SeoCheckResult
    {
        $plain = $this->stripHtml($content);
        $wordCount = $this->wordCount($plain);

        if ($wordCount < 30) {
            return new SeoCheckResult(
                key: 'readability',
                label: 'Readability',
                level: 'bad',
                message: 'Not enough content to score readability.',
                weight: 10,
            );
        }

        $score = $this->fleschReadingEase($plain);
        $formatted = number_format($score, 1);

        if ($score >= self::FLESCH_GOOD_MIN) {
            return new SeoCheckResult(
                key: 'readability',
                label: 'Readability',
                level: 'good',
                message: "Flesch score {$formatted} — easy to read.",
                weight: 10,
            );
        }

        if ($score >= self::FLESCH_WARNING_MIN) {
            return new SeoCheckResult(
                key: 'readability',
                label: 'Readability',
                level: 'warning',
                message: "Flesch score {$formatted} — consider shorter sentences.",
                weight: 10,
            );
        }

        return new SeoCheckResult(
            key: 'readability',
            label: 'Readability',
            level: 'bad',
            message: "Flesch score {$formatted} — text is hard to read.",
            weight: 10,
        );
    }

    // -------------------------------------------------------------------------
    // Text helpers
    // -------------------------------------------------------------------------

    private function stripHtml(string $html): string
    {
        $withSpaces = preg_replace('/<(p|br|div|h[1-6]|li)[^>]*>/i', ' ', $html) ?? $html;
        $stripped = trim(strip_tags($withSpaces));
        $normalised = preg_replace('/\s+/', ' ', $stripped) ?? $stripped;

        return trim((string) $normalised);
    }

    private function wordCount(string $text): int
    {
        if (trim($text) === '') {
            return 0;
        }

        return str_word_count($text, 0, '0123456789ÀÁÂÃÄÅàáâãäåÈÉÊËèéêëÌÍÎÏìíîïÒÓÔÕÖØòóôõöøÙÚÛÜùúûüÑñ-');
    }

    private function firstNWords(string $text, int $n): string
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_slice($words, 0, $n));
    }

    private function containsCaseInsensitive(string $haystack, string $needle): bool
    {
        $needle = trim($needle);

        if ($needle === '') {
            return false;
        }

        return mb_stripos($haystack, $needle) !== false;
    }

    /**
     * Count occurrences of $keyword as either a multi-word phrase or
     * any of its constituent tokens, and express that as a percentage
     * of total words.
     */
    private function keywordDensityPercent(string $content, string $keyword, int $totalWords): float
    {
        if ($totalWords === 0) {
            return 0.0;
        }

        $needle = trim($keyword);

        if ($needle === '') {
            return 0.0;
        }

        $pattern = '/'.preg_quote($needle, '/').'/iu';
        $matches = preg_match_all($pattern, $content) ?: 0;

        // Phrase keywords occupy N word slots; divide by phrase length
        // so density reflects "share of words", not "share of tokens".
        $phraseLength = max(1, count(preg_split('/\s+/', $needle, -1, PREG_SPLIT_NO_EMPTY) ?: ['x']));

        $occupied = $matches * $phraseLength;

        return min(100.0, ($occupied / $totalWords) * 100);
    }

    /**
     * Classic Flesch Reading Ease score.
     *   206.835 - 1.015 * (words/sentences) - 84.6 * (syllables/words)
     *
     * Higher is easier; 60-70 is plain English.
     */
    private function fleschReadingEase(string $text): float
    {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $wordCount = count($words);

        if ($wordCount === 0) {
            return 0.0;
        }

        $sentences = max(1, preg_match_all('/[.!?]+/', $text));
        $syllables = 0;

        foreach ($words as $word) {
            $syllables += $this->estimateSyllables($word);
        }

        return 206.835
            - 1.015 * ($wordCount / $sentences)
            - 84.6 * ($syllables / $wordCount);
    }

    /**
     * Rough English syllable estimator. Good enough for a heuristic.
     */
    private function estimateSyllables(string $word): int
    {
        $word = strtolower(preg_replace('/[^a-zA-Z]/', '', $word) ?? '');

        if ($word === '') {
            return 0;
        }

        // Silent-e adjustment.
        $word = (string) preg_replace('/e$/', '', $word);

        $matches = preg_match_all('/[aeiouy]+/', $word) ?: 0;

        return max(1, $matches);
    }
}
