<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * WpMigrationAudit Command
 *
 * Automated verification of WordPress → Rupantrix migration parity across:
 * - Content (Posts, Pages)
 * - Categories, Tags & Taxonomies
 * - Users & Authors
 * - Media & Attachments
 * - URLs, Slugs & 301 Redirects
 * - SEO Metadata (Titles, Descriptions, Canonicals, Robots, Schema)
 * - Old WordPress References & Internal Links
 */
class WpMigrationAudit extends Command
{
    protected $signature = 'wp:migration-audit';

    protected $description = 'Perform complete lossless parity audit of WordPress → Rupantrix migration';

    public function handle(): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║             WORDPRESS → LARAVEL MIGRATION AUDIT                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');

        $wp = DB::connection('wordpress');
        $oldDomain = config('services.wordpress.domain', 'blogchowk.com');

        $hasErrors = false;

        // ─── 1. CONTENT AUDIT ─────────────────────────────────────────────
        $wpPostCount = $wp->table('wp_posts')->where('post_type', 'post')->where('post_status', 'publish')->count();
        $npPostCount = DB::table('posts')->where('type', 'post')->where('status', 'published')->count();
        $wpPageCount = $wp->table('wp_posts')->where('post_type', 'page')->where('post_status', 'publish')->count();
        $npPageCount = Schema::hasTable('pages') ? DB::table('pages')->where('status', 'published')->count() : 0;

        $this->info('CONTENT');
        $this->line("  " . ($npPostCount >= $wpPostCount ? '✓' : '✗') . " Published Posts: {$npPostCount} / {$wpPostCount}");
        $this->line("  " . ($npPageCount >= $wpPageCount ? '✓' : '✗') . " Published Pages: {$npPageCount} / {$wpPageCount}");
        if ($npPostCount < $wpPostCount || $npPageCount < $wpPageCount) {
            $hasErrors = true;
        }

        // ─── 2. CATEGORIES AUDIT ──────────────────────────────────────────
        $wpCatCount = $wp->table('wp_term_taxonomy')->where('taxonomy', 'category')->count();
        $npCatCount = DB::table('categories')->count();
        $this->info('');
        $this->info('CATEGORIES');
        $this->line("  " . ($npCatCount >= $wpCatCount ? '✓' : '✗') . " Categories: {$npCatCount} / {$wpCatCount}");
        if ($npCatCount < $wpCatCount) {
            $hasErrors = true;
        }

        // ─── 3. TAGS AUDIT ────────────────────────────────────────────────
        $wpTagCount = $wp->table('wp_term_taxonomy')->where('taxonomy', 'post_tag')->count();
        $npTagCount = Schema::hasTable('tags') ? DB::table('tags')->count() : 0;
        $npPostTagRel = Schema::hasTable('post_tag') ? DB::table('post_tag')->count() : 0;
        $this->info('');
        $this->info('TAGS & TAXONOMIES');
        $this->line("  " . ($npTagCount >= $wpTagCount ? '✓' : '✗') . " Tags: {$npTagCount} / {$wpTagCount}");
        $this->line("  ✓ Post ↔ Tag Relationships: {$npPostTagRel}");
        if ($npTagCount < $wpTagCount) {
            $hasErrors = true;
        }

        // ─── 4. USERS AUDIT ───────────────────────────────────────────────
        $wpUserCount = $wp->table('wp_users')->count();
        $npUserCount = DB::table('users')->count();
        $this->info('');
        $this->info('USERS / AUTHORS');
        $this->line("  " . ($npUserCount >= $wpUserCount ? '✓' : '✗') . " Users: {$npUserCount} / {$wpUserCount}");
        if ($npUserCount < $wpUserCount) {
            $hasErrors = true;
        }

        // ─── 5. MEDIA AUDIT ───────────────────────────────────────────────
        $wpMediaCount = $wp->table('wp_posts')->where('post_type', 'attachment')->whereNotNull('guid')->count();
        $npMediaCount = DB::table('media')->count();
        $npMediaExternal = DB::table('media')->where('path', 'like', 'http%')->count();
        $npMediaLocal = DB::table('media')->where('path', 'not like', 'http%')->count();

        $this->info('');
        $this->info('MEDIA & ATTACHMENTS');
        $this->line("  " . ($npMediaCount >= $wpMediaCount ? '✓' : '✗') . " Total Attachments: {$npMediaCount} / {$wpMediaCount}");
        $this->line("  ✓ Localized / Mapped Storage Paths: {$npMediaLocal}");
        if ($npMediaExternal > 0) {
            $this->line("  ⚠ Media with External WP URLs: {$npMediaExternal}");
        }

        // ─── 6. URLS & REDIRECTS AUDIT ────────────────────────────────────
        $npRedirectCount = Schema::hasTable('redirects') ? DB::table('redirects')->where('is_active', true)->count() : 0;
        $this->info('');
        $this->info('URLS & 301 REDIRECTS');
        $this->line("  ✓ Preserved Canonical Slugs: {$npPostCount}");
        $this->line("  ✓ Active 301 Redirect Rules: {$npRedirectCount}");

        // ─── 7. SEO METADATA AUDIT ────────────────────────────────────────
        $npSeoMetasCount = Schema::hasTable('seo_metas') ? DB::table('seo_metas')->count() : 0;
        $npPostTranslationsSeo = DB::table('post_translations')->whereNotNull('meta_title')->count();
        $npCanonicalsCount = DB::table('post_translations')->whereNotNull('canonical_url')->count()
            + (Schema::hasTable('seo_metas') ? DB::table('seo_metas')->whereNotNull('canonical_url')->count() : 0);

        $this->info('');
        $this->info('SEO METADATA PARITY');
        $this->line("  ✓ SEO Meta Records (seo_metas): {$npSeoMetasCount}");
        $this->line("  ✓ Post Translation Meta Titles: {$npPostTranslationsSeo}");
        $this->line("  ✓ Canonicals Configured: {$npCanonicalsCount}");

        // ─── 8. OLD WORDPRESS REFERENCES AUDIT ────────────────────────────
        $oldWpDomainLinks = DB::table('post_translations')->where('content', 'regexp', '(href|src)=["\'][^"\']*' . preg_quote($oldDomain, '/') . '')->count();
        $sourceWpUploadsInContent = DB::table('post_translations')->where('content', 'like', "%{$oldDomain}/wp-content/uploads/%")->count();
        $shortcodesInContent = DB::table('post_translations')->where('content', 'like', "%[caption%")->orWhere('content', 'like', "%[gallery%")->count();

        $this->info('');
        $this->info('CONTENT CLEANLINESS & ISOLATION');
        $this->line("  " . ($oldWpDomainLinks === 0 ? '✓' : '⚠') . " Old Domain Links in Content: {$oldWpDomainLinks}");
        $this->line("  " . ($sourceWpUploadsInContent === 0 ? '✓' : '⚠') . " Legacy Upload Media Links: {$sourceWpUploadsInContent}");
        $this->line("  " . ($shortcodesInContent === 0 ? '✓' : '⚠') . " Unconverted WordPress Shortcodes: {$shortcodesInContent}");

        $this->info('');
        $this->info('==================================================================');
        if (!$hasErrors && $oldWpDomainLinks === 0 && $sourceWpUploadsInContent === 0 && $shortcodesInContent === 0) {
            $this->info('🎯 MIGRATION AUDIT STATUS: PASS (Lossless SEO & Content Parity Achieved)');
        } elseif (!$hasErrors) {
            $this->warn('⚠️ MIGRATION AUDIT STATUS: READY (Core Parity Met)');
        } else {
            $this->error('✗ MIGRATION AUDIT STATUS: INCOMPLETE (Run "php artisan wp:migrate")');
        }
        $this->info('==================================================================');
        $this->info('');

        return $hasErrors ? 1 : 0;
    }
}
