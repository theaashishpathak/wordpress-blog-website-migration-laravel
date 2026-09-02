<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Migration\UserMigrationService;
use App\Services\Migration\CategoryMigrationService;
use App\Services\Migration\TagMigrationService;
use App\Services\Migration\MediaMigrationService;
use App\Services\Migration\PageMigrationService;
use App\Services\Migration\PostMigrationService;
use App\Services\Migration\CommentMigrationService;
use App\Services\Migration\SeoMigrationService;
use App\Services\Migration\RedirectMigrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MigrateFromWordPress Command
 *
 * Full lossless WordPress → Rupantrix migration pipeline.
 *
 * Execution Order:
 * 1. Users
 * 2. Categories
 * 3. Tags & Post-Tag Relationships
 * 4. Media & Featured Images
 * 5. Pages & Page SEO
 * 6. Posts, Content Transformation & Post SEO
 * 7. Comments
 * 8. SEO Metadata (RankMath / Yoast)
 * 9. 301 Permanent Redirects
 */
class MigrateFromWordPress extends Command
{
    protected $signature = 'wp:migrate
                            {--fresh : Truncate Rupantrix tables before migrating}
                            {--users : Only migrate users}
                            {--cats : Only migrate categories}
                            {--tags : Only migrate tags}
                            {--media : Only migrate media}
                            {--pages : Only migrate pages}
                            {--posts : Only migrate posts}
                            {--comments : Only migrate comments}
                            {--seo : Only migrate SEO metadata}
                            {--redirects : Only generate redirect rules}
                            {--localize-media : Download physical media files to local storage}';

    protected $description = 'Migrate WordPress data into Rupantrix with content, media, URL, relationship, and SEO parity';

    public function handle(
        UserMigrationService $users,
        CategoryMigrationService $categories,
        TagMigrationService $tags,
        MediaMigrationService $media,
        PageMigrationService $pages,
        PostMigrationService $posts,
        CommentMigrationService $comments,
        SeoMigrationService $seo,
        RedirectMigrationService $redirects,
    ): int {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║   WordPress → Rupantrix Lossless Migration & SEO Parity Pipeline ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('fresh')) {
            if (!$this->confirm('⚠️  This will DELETE existing Rupantrix posts, media, categories, tags, pages, and comments. Are you sure?')) {
                $this->info('Aborted.');
                return 0;
            }
            $this->truncateTables();
        }

        $runAll = !$this->option('users')
            && !$this->option('cats')
            && !$this->option('tags')
            && !$this->option('media')
            && !$this->option('pages')
            && !$this->option('posts')
            && !$this->option('comments')
            && !$this->option('seo')
            && !$this->option('redirects');

        // ─── STEP 1: Users ────────────────────────────────────────────────
        if ($runAll || $this->option('users')) {
            $this->section('Step 1 of 9 — Migrating Users');
            $users->setCommand($this)->run();
        }

        // ─── STEP 2: Categories ───────────────────────────────────────────
        if ($runAll || $this->option('cats')) {
            $this->section('Step 2 of 9 — Migrating Categories');
            $categories->setCommand($this)->run();
        }

        // ─── STEP 3: Tags ─────────────────────────────────────────────────
        if ($runAll || $this->option('tags')) {
            $this->section('Step 3 of 9 — Migrating Tags & Taxonomies');
            $tags->setCommand($this)->run();
        }

        // ─── STEP 4: Media ────────────────────────────────────────────────
        if ($runAll || $this->option('media')) {
            $this->section('Step 4 of 9 — Migrating Media Attachments & Metadata');
            if ($this->option('localize-media')) {
                $media->enableLocalization(true);
            }
            $media->setCommand($this)->run();
        }

        // ─── STEP 5: Pages ────────────────────────────────────────────────
        if ($runAll || $this->option('pages')) {
            $this->section('Step 5 of 9 — Migrating Pages');
            $pages->setCommand($this)->run();
        }

        // ─── STEP 6: Posts & Content Transformation ───────────────────────
        if ($runAll || $this->option('posts')) {
            $this->section('Step 6 of 9 — Migrating Posts & Content URLs');
            $posts->setCommand($this)->run();
        }

        // ─── STEP 7: Comments ─────────────────────────────────────────────
        if ($runAll || $this->option('comments')) {
            $this->section('Step 7 of 9 — Migrating Comments');
            $comments->setCommand($this)->run();
        }

        // ─── STEP 8: SEO Metadata ─────────────────────────────────────────
        if ($runAll || $this->option('seo')) {
            $this->section('Step 8 of 9 — Migrating SEO Metadata (RankMath/Yoast)');
            $seo->setCommand($this)->run();
        }

        // ─── STEP 9: 301 Redirects ────────────────────────────────────────
        if ($runAll || $this->option('redirects')) {
            $this->section('Step 9 of 9 — Generating 301 Permanent Redirects');
            $redirects->setCommand($this)->run();
        }

        $this->info('');
        $this->info('✅  Lossless WordPress Migration Completed Successfully!');
        $this->info('Run "php artisan wp:migration-audit" to verify parity.');
        $this->info('');

        return 0;
    }

    private function truncateTables(): void
    {
        $this->warn('Truncating Rupantrix tables in safe dependency order...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('post_translations')->truncate();
        DB::table('posts')->truncate();
        if (Schema::hasTable('post_tag')) DB::table('post_tag')->truncate();
        if (Schema::hasTable('tags')) DB::table('tags')->truncate();
        if (Schema::hasTable('tag_translations')) DB::table('tag_translations')->truncate();
        if (Schema::hasTable('page_translations')) DB::table('page_translations')->truncate();
        if (Schema::hasTable('pages')) DB::table('pages')->truncate();
        if (Schema::hasTable('comments')) DB::table('comments')->truncate();
        if (Schema::hasTable('seo_metas')) DB::table('seo_metas')->truncate();
        if (Schema::hasTable('redirects')) DB::table('redirects')->truncate();
        DB::table('media')->truncate();
        DB::table('category_translations')->truncate();
        DB::table('categories')->truncate();
        DB::table('users')->whereIn('portal_type', ['author', 'visitor'])->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Tables cleared.');
    }

    private function section(string $title): void
    {
        $this->info('');
        $this->info("── {$title} ──");
    }
}
