<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WpBackfillParity extends Command
{
    protected $signature = 'wp:backfill-parity';
    protected $description = 'Backfill excerpts, canonical URLs and clean content artifacts for 100% parity';

    public function handle(): int
    {
        $this->info('Starting backfill for excerpts, canonical URLs, and content artifacts...');

        // 1. Backfill and Clean Excerpts (decode entities like &nbsp;)
        $allTranslations = DB::table('post_translations')->get();
        $this->info("Scanning {$allTranslations->count()} post translations to clean excerpts.");
        $excerptCount = 0;

        foreach ($allTranslations as $p) {
            $currentExcerpt = (string) $p->excerpt;
            $needsUpdate = false;
            $newExcerpt = $currentExcerpt;

            if (empty(trim($currentExcerpt)) && $p->content && trim($p->content) !== '') {
                $stripped = html_entity_decode(strip_tags($p->content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $stripped = trim(preg_replace('/\s+/', ' ', str_replace("\xc2\xa0", ' ', $stripped)));
                if ($stripped !== '') {
                    $newExcerpt = Str::limit($stripped, 180, '...');
                    $needsUpdate = true;
                }
            } elseif (str_contains($currentExcerpt, '&nbsp;') || str_contains($currentExcerpt, '&amp;') || str_contains($currentExcerpt, '&quot;') || str_contains($currentExcerpt, '&#039;')) {
                $decoded = html_entity_decode($currentExcerpt, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $newExcerpt = trim(preg_replace('/\s+/', ' ', str_replace("\xc2\xa0", ' ', $decoded)));
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                DB::table('post_translations')->where('id', $p->id)->update([
                    'excerpt' => $newExcerpt,
                ]);
                $excerptCount++;
            }
        }
        $this->line("  ✓ Cleaned / updated {$excerptCount} excerpts.");

        // 2. Clean Content Artifacts (like "Visit our website:- /")
        $postsWithArtifacts = DB::table('post_translations')
            ->where('content', 'like', '%:- /%')
            ->get();

        $this->info("Found {$postsWithArtifacts->count()} posts with ':- /' artifact.");
        $artifactCount = 0;

        foreach ($postsWithArtifacts as $p) {
            $cleaned = preg_replace('/(:-\s*)\/(?!\w)/i', '$1' . config('app.name', 'BlogChowk'), $p->content);
            DB::table('post_translations')->where('id', $p->id)->update([
                'content' => $cleaned,
            ]);
            $artifactCount++;
        }
        $this->line("  ✓ Cleaned {$artifactCount} content artifacts.");

        // 3. Backfill Canonicals in post_translations and seo_metas
        $translations = DB::table('post_translations')->select('id', 'post_id', 'slug', 'canonical_url')->get();
        $canonicalCount = 0;

        foreach ($translations as $t) {
            if (!$t->canonical_url && $t->slug) {
                $canonical = url('/' . $t->slug);
                DB::table('post_translations')->where('id', $t->id)->update([
                    'canonical_url' => $canonical,
                ]);

                DB::table('seo_metas')
                    ->where('seoable_type', 'App\\Models\\Post')
                    ->where('seoable_id', $t->post_id)
                    ->update([
                        'canonical_url' => $canonical,
                    ]);

                $canonicalCount++;
            }
        }
        $this->line("  ✓ Backfilled {$canonicalCount} canonical URLs.");

        $this->info('Parity backfill completed successfully.');
        return 0;
    }
}
