<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Migration\UserMigrationService;
use App\Services\Migration\CategoryMigrationService;
use App\Services\Migration\MediaMigrationService;
use App\Services\Migration\PostMigrationService;
use Illuminate\Support\Facades\DB;

/**
 * MigrateFromWordPress Command
 *
 * WHY THIS EXISTS:
 * This is the single entry point for the entire WordPress → NewsPilot migration.
 * Instead of running 4 separate commands and hoping you remembered the right order,
 * this command runs everything in the correct sequence automatically.
 *
 * WHY ORDER MATTERS:
 * NewsPilot's `posts` table has foreign keys pointing to `users`, `categories`,
 * and `media`. If you try to insert a post before its author exists in the users
 * table, MySQL will throw a foreign key constraint error and the whole thing fails.
 * This command guarantees the correct insertion order every time.
 *
 * HOW TO RUN:
 * php artisan wp:migrate          — migrate everything
 * php artisan wp:migrate --fresh  — wipe NewsPilot tables first, then migrate
 * php artisan wp:migrate --users  — only migrate users
 * php artisan wp:migrate --cats   — only migrate categories
 * php artisan wp:migrate --media  — only migrate media
 * php artisan wp:migrate --posts  — only migrate posts
 */
class MigrateFromWordPress extends Command
{
    protected $signature = 'wp:migrate
                            {--fresh : Truncate NewsPilot tables before migrating}
                            {--users : Only migrate users}
                            {--cats : Only migrate categories}
                            {--media : Only migrate media}
                            {--posts : Only migrate posts}';

    protected $description = 'Migrate all WordPress data into NewsPilot database tables';

    public function handle(
        UserMigrationService $users,
        CategoryMigrationService $categories,
        MediaMigrationService $media,
        PostMigrationService $posts,
    ): int {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║   WordPress → NewsPilot Migration Tool   ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        // --fresh flag: wipe NewsPilot tables before starting
        // WHY: If you've run this before and want a clean slate, this saves
        // you from manually truncating tables in phpMyAdmin.
        if ($this->option('fresh')) {
            if (!$this->confirm('⚠️  This will DELETE all existing NewsPilot data. Are you sure?')) {
                $this->info('Aborted.');
                return 0;
            }
            $this->truncateTables();
        }

        $runAll = !$this->option('users')
            && !$this->option('cats')
            && !$this->option('media')
            && !$this->option('posts');

        // ─── STEP 1: Users ────────────────────────────────────────────────
        // Must be first. Every post needs an author_id that points to a real
        // user in NewsPilot's users table.
        if ($runAll || $this->option('users')) {
            $this->section('Step 1 of 4 — Migrating Users');
            $users->setCommand($this)->run();
        }

        // ─── STEP 2: Categories ───────────────────────────────────────────
        // Must come before posts. Every post needs a category_id.
        if ($runAll || $this->option('cats')) {
            $this->section('Step 2 of 4 — Migrating Categories');
            $categories->setCommand($this)->run();
        }

        // ─── STEP 3: Media (Featured Images) ──────────────────────────────
        // Must come before posts. Every post's featured_image_id points here.
        if ($runAll || $this->option('media')) {
            $this->section('Step 3 of 4 — Migrating Featured Images');
            $media->setCommand($this)->run();
        }

        // ─── STEP 4: Posts ────────────────────────────────────────────────
        // Last. By now users, categories, and media all exist so all FKs resolve.
        if ($runAll || $this->option('posts')) {
            $this->section('Step 4 of 4 — Migrating Posts');
            $posts->setCommand($this)->run();
        }

        $this->info('');
        $this->info('✅  Migration complete!');
        $this->info('');

        return 0;
    }

    /**
     * Wipe NewsPilot tables in reverse dependency order.
     *
     * WHY REVERSE ORDER:
     * posts → media → categories → users (reverse of insert order).
     * If you truncate users first while posts still reference them,
     * MySQL throws a foreign key error. Reverse order avoids that.
     */
    private function truncateTables(): void
    {
        $this->warn('Truncating NewsPilot tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('post_translations')->truncate();
        DB::table('posts')->truncate();
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
