<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\PostTranslation;
use App\Services\Content\HtmlSanitizer;
use Illuminate\Console\Command;

/**
 * One-off migration for legacy posts whose content lost paragraph
 * structure (single-line walls of text, raw plain text without <p>
 * tags, or Summernote artefacts that collapsed on render).
 *
 *   php artisan posts:fix-content                 # all translations
 *   php artisan posts:fix-content --id=42         # single translation
 *   php artisan posts:fix-content --dry-run       # preview only
 *   php artisan posts:fix-content --since=30d     # touched in the last 30 days
 *
 * What it does, per translation:
 *   1. Detect when content has no block tags at all → wrap text blocks
 *      separated by blank lines or single line breaks into <p>...</p>.
 *   2. Re-flow Markdown-style headings ("## Title") into <h2>…</h2>.
 *   3. Pass the result through HtmlSanitizer so the saved HTML
 *      matches the editor's expected format.
 */
final class FixPostContent extends Command
{
    /** @var string */
    protected $signature = 'posts:fix-content
        {--id= : Restore a single PostTranslation row by id}
        {--since= : Only process translations updated within e.g. "30d", "6h"}
        {--dry-run : Show what would change without writing}
        {--limit=0 : Cap the number of rows processed (0 = no cap)}';

    /** @var string */
    protected $description = 'Restore paragraph structure on legacy post translations.';

    public function handle(HtmlSanitizer $sanitizer): int
    {
        $query = PostTranslation::query()->orderBy('id');

        if ($id = $this->option('id')) {
            $query->whereKey((int) $id);
        }

        if ($since = $this->option('since')) {
            $query->where('updated_at', '>=', $this->parseSince($since));
        }

        if (($limit = (int) $this->option('limit')) > 0) {
            $query->limit($limit);
        }

        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;
        $scanned = 0;

        $query->chunkById(100, function ($rows) use ($sanitizer, $dryRun, &$changed, &$scanned) {
            foreach ($rows as $row) {
                $scanned++;
                $original = (string) $row->content;

                if ($original === '') {
                    continue;
                }

                $rebuilt = $this->rebuild($original);
                $clean = $sanitizer->clean($rebuilt);

                if (trim($clean) === trim($original)) {
                    continue;
                }

                $this->line(sprintf(
                    '<comment>#%d</comment> %s → %s chars',
                    $row->id,
                    number_format(mb_strlen($original)),
                    number_format(mb_strlen($clean)),
                ));

                if (! $dryRun) {
                    $row->content = $clean;
                    $row->saveQuietly();
                }

                $changed++;
            }
        });

        $this->newLine();
        $this->info(sprintf(
            'Scanned %d translation(s); %d %s.',
            $scanned,
            $changed,
            $dryRun ? 'would change' : 'changed',
        ));

        return self::SUCCESS;
    }

    /**
     * Decide whether the content already has structured HTML, and if not,
     * wrap it into paragraphs that the prose renderer will format nicely.
     */
    private function rebuild(string $html): string
    {
        // Strip a stray BOM and normalise newlines.
        $html = preg_replace('/^\xEF\xBB\xBF/', '', $html) ?? $html;
        $html = str_replace(["\r\n", "\r"], "\n", $html);

        // If there are real block tags already, leave the structure alone
        // and just normalise stray double-spaces / NBSP. The sanitizer
        // (called by the caller) will take care of the rest.
        if (preg_match('#<(p|h[1-6]|ul|ol|blockquote|pre|figure|table)\b#i', $html) === 1) {
            return $html;
        }

        // Drop a single wrapper <div>...</div> that Summernote sometimes
        // emits around an otherwise unstructured blob.
        $html = preg_replace('#^\s*<div[^>]*>([\s\S]*)</div>\s*$#i', '$1', $html) ?? $html;

        // Convert "## Heading" / "### Heading" markdown-style lines to h2/h3.
        $html = preg_replace_callback(
            '/^[ \t]*(#{2,4})[ \t]+(.+?)[ \t]*$/m',
            static function (array $m): string {
                $level = strlen($m[1]); // 2..4
                return sprintf('<h%d>%s</h%d>', $level, trim($m[2]), $level);
            },
            $html,
        ) ?? $html;

        // Split on a blank line into "paragraphs"; single line breaks
        // inside a block become <br> so deliberate poetry-style breaks
        // survive.
        $blocks = preg_split('/\n[ \t]*\n+/u', trim($html));
        $out = [];
        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }
            // Already a block-level tag we generated above? Keep as-is.
            if (preg_match('#^<(h[1-6]|ul|ol|blockquote|pre|figure|table|hr)\b#i', $block) === 1) {
                $out[] = $block;
                continue;
            }
            // Otherwise wrap and convert internal newlines to <br>.
            $out[] = '<p>'.nl2br($block, false).'</p>';
        }

        return implode("\n", $out);
    }

    private function parseSince(string $expression): \DateTimeInterface
    {
        $expression = trim(strtolower($expression));
        if (preg_match('/^(\d+)([smhd])$/', $expression, $m) !== 1) {
            $this->warn("Could not parse --since={$expression}; using 'now -1 day'");
            return now()->subDay();
        }

        $value = (int) $m[1];

        return match ($m[2]) {
            's' => now()->subSeconds($value),
            'm' => now()->subMinutes($value),
            'h' => now()->subHours($value),
            'd' => now()->subDays($value),
        };
    }
}
