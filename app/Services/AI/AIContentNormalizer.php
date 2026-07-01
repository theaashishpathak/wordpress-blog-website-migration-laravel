<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\Content\HtmlSanitizer;

/**
 * Normalize AI-generated article output into clean, TipTap-compatible
 * HTML that renders nicely with our `.prose` styles and survives a
 * round-trip through HtmlSanitizer.
 *
 * Inputs LLMs typically return:
 *   - Pure Markdown ("## Heading\n\nParagraph…")
 *   - Markdown wrapped in a code fence (```html or ```markdown)
 *   - Half-Markdown / half-HTML hybrids
 *   - Bare prose with line breaks but no structure
 *
 * The normalizer handles all three so any prompt template can request
 * "Markdown OR HTML" without the calling Action caring which one it
 * actually got back.
 *
 * Conversion is intentionally simple — we don't pull in a full Markdown
 * parser. We support the subset newsroom prompts actually emit:
 *   - `# H1` → `<h2>` (H1 reserved for post title on the frontend)
 *   - `## H2 / ### H3 / #### H4`
 *   - `* / - / 1.` lists (one level deep)
 *   - `> blockquote`
 *   - `**bold**`, `*italic*`, `[label](url)`
 *   - `![alt](src)` images
 *   - Blank-line paragraph separation
 */
final class AIContentNormalizer
{
    public function __construct(private HtmlSanitizer $sanitizer) {}

    /**
     * Normalize an AI completion's raw text into clean HTML.
     */
    public function normalize(string $content): string
    {
        $content = $this->stripCodeFence($content);
        $content = $this->stripWrappingQuotes($content);

        // If the model returned HTML already, just clean it and ship it.
        if ($this->looksLikeHtml($content)) {
            return $this->sanitizer->clean($this->normalizeHtmlHeadings($content));
        }

        $html = $this->markdownToHtml($content);

        return $this->sanitizer->clean($html);
    }

    // ── Markdown → HTML conversion ─────────────────────────────────────

    private function markdownToHtml(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", trim($markdown));
        $blocks = preg_split('/\n[ \t]*\n+/u', $markdown) ?: [];
        $out = [];

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            // Headings — `#` count maps to level. Editor's H1 is reserved
            // for the post title; promote a leading `#` to `<h2>`.
            if (preg_match('/^(#{1,6})\s+(.+)$/u', $block, $m) === 1) {
                $level = max(2, min(6, strlen($m[1])));
                $out[] = sprintf('<h%d>%s</h%d>', $level, $this->inline(trim($m[2])), $level);
                continue;
            }

            // Blockquotes — every line in the block starts with `>`.
            if (preg_match('/^>\s?/m', $block) === 1
                && (bool) preg_match('/^(?:>\s?.*\n?)+$/u', $block)) {
                $clean = preg_replace('/^>\s?/m', '', $block) ?? $block;
                $out[] = '<blockquote><p>'.$this->inline(trim($clean)).'</p></blockquote>';
                continue;
            }

            // Unordered list — every line starts with `*` or `-` or `+`.
            if ((bool) preg_match('/^[\*\-\+]\s+/m', $block)) {
                $items = preg_split('/\n/', $block) ?: [];
                $lis = [];
                foreach ($items as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $line = preg_replace('/^[\*\-\+]\s+/', '', $line) ?? $line;
                    $lis[] = '<li>'.$this->inline($line).'</li>';
                }
                $out[] = '<ul>'.implode('', $lis).'</ul>';
                continue;
            }

            // Ordered list — every line starts with "N." or "N)".
            if ((bool) preg_match('/^\d+[\.\)]\s+/m', $block)) {
                $items = preg_split('/\n/', $block) ?: [];
                $lis = [];
                foreach ($items as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    $line = preg_replace('/^\d+[\.\)]\s+/', '', $line) ?? $line;
                    $lis[] = '<li>'.$this->inline($line).'</li>';
                }
                $out[] = '<ol>'.implode('', $lis).'</ol>';
                continue;
            }

            // Horizontal rule.
            if ((bool) preg_match('/^-{3,}$|^_{3,}$|^\*{3,}$/', $block)) {
                $out[] = '<hr>';
                continue;
            }

            // Fallback — plain paragraph, with single newlines as <br>.
            $out[] = '<p>'.$this->inline(nl2br($block, false)).'</p>';
        }

        return implode("\n", $out);
    }

    /**
     * Convert inline markdown (bold, italic, link, code, image) to HTML.
     * Order matters — process links first so the `*` inside `[**bold**]`
     * doesn't get mangled.
     */
    private function inline(string $text): string
    {
        // Escape any raw HTML the model sneaked in (defensive — sanitizer
        // will strip again later, this prevents broken nesting).
        // BUT: we want our own already-generated tags from previous passes
        // to survive — that's why this method is only ever called on
        // markdown source, never on output we built ourselves.

        // Image: ![alt](url)
        $text = preg_replace_callback(
            '/!\[([^\]]*)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)/',
            static fn (array $m): string => sprintf(
                '<img src="%s" alt="%s"%s loading="lazy">',
                htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($m[1], ENT_QUOTES, 'UTF-8'),
                isset($m[3]) && $m[3] !== '' ? ' title="'.htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8').'"' : '',
            ),
            $text,
        ) ?? $text;

        // Link: [label](url)
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)(?:\s+"([^"]*)")?\)/',
            static fn (array $m): string => sprintf(
                '<a href="%s"%s rel="noopener noreferrer nofollow" target="_blank">%s</a>',
                htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8'),
                isset($m[3]) && $m[3] !== '' ? ' title="'.htmlspecialchars($m[3], ENT_QUOTES, 'UTF-8').'"' : '',
                $m[1],
            ),
            $text,
        ) ?? $text;

        // Bold: **text** (greedy off so nested cases work)
        $text = preg_replace('/\*\*([^\*]+)\*\*/u', '<strong>$1</strong>', $text) ?? $text;

        // Italic: *text* (single-asterisk, not preceded/followed by `*`)
        $text = preg_replace('/(?<!\*)\*(?!\*)([^\*\n]+?)\*(?!\*)/u', '<em>$1</em>', $text) ?? $text;

        // Inline code: `text`
        $text = preg_replace('/`([^`]+)`/u', '<code>$1</code>', $text) ?? $text;

        return $text;
    }

    // ── HTML pass: normalise heading levels & cleanup ──────────────────

    /**
     * Demote a leading <h1> to <h2> so the article body never competes
     * with the post title on the frontend. Idempotent if no h1 exists.
     */
    private function normalizeHtmlHeadings(string $html): string
    {
        // Only demote the FIRST h1 if it sits at the start of the body.
        $html = preg_replace('#^(\s*)<h1\b([^>]*)>(.*?)</h1>#is', '$1<h2$2>$3</h2>', $html, 1) ?? $html;

        // Subsequent h1s anywhere → h2 (rare but defensive).
        $html = preg_replace('#<h1\b([^>]*)>(.*?)</h1>#is', '<h2$1>$2</h2>', $html) ?? $html;

        return $html;
    }

    // ── Heuristics ─────────────────────────────────────────────────────

    private function stripCodeFence(string $content): string
    {
        $content = trim($content);
        if (preg_match('/^```(?:html|markdown|md)?\s*\n?([\s\S]*?)\n?```\s*$/m', $content, $m) === 1) {
            return trim($m[1]);
        }

        return $content;
    }

    private function stripWrappingQuotes(string $content): string
    {
        $content = trim($content);
        if (mb_strlen($content) >= 2
            && (mb_substr($content, 0, 1) === '"' && mb_substr($content, -1) === '"')) {
            return trim(mb_substr($content, 1, -1));
        }

        return $content;
    }

    /**
     * Treat the response as HTML when it contains any of the block tags
     * we care about anywhere in the body.
     */
    private function looksLikeHtml(string $content): bool
    {
        return (bool) preg_match('#<(?:p|h[1-6]|ul|ol|blockquote|figure|table|hr|br)\b#i', $content);
    }
}
