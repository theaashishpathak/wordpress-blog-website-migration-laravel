<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RedirectMigrationService
 *
 * Populates Rupantrix's `redirects` table with 301 permanent redirects for legacy
 * WordPress URL patterns (e.g. /?p=123, /year/month/slug, old page paths).
 */
class RedirectMigrationService extends BaseMigrationService
{
    public function run(): void
    {
        if (!Schema::hasTable('redirects')) {
            $this->warn('Redirects table does not exist.');
            return;
        }

        $this->info('Starting Redirects generation...');

        $posts = DB::table('posts')
            ->join('post_translations', 'posts.id', '=', 'post_translations.post_id')
            ->select('posts.id', 'posts.published_at', 'post_translations.slug')
            ->get();

        $count = 0;

        foreach ($posts as $post) {
            $slug = $post->slug;
            $destinationUrl = '/' . $slug;

            // 1. Map /?p={id} -> /{slug}
            $this->createRedirect('/?p=' . $post->id, $destinationUrl, 'WordPress post ID parameter');

            // 2. Map Date-based permalink /YYYY/MM/{slug} -> /{slug} if published_at exists
            if ($post->published_at) {
                $date = new \DateTime($post->published_at);
                $year = $date->format('Y');
                $month = $date->format('m');
                $datePattern = "/{$year}/{$month}/{$slug}";
                $this->createRedirect($datePattern, $destinationUrl, 'Date-based WordPress permalink');
                $this->createRedirect($datePattern . '/', $destinationUrl, 'Date-based WordPress permalink trailing slash');
            }

            $count++;
        }

        // Map standard WordPress feeds to Rupantrix feeds
        $this->createRedirect('/feed', '/feed.xml', 'WordPress global RSS feed');
        $this->createRedirect('/feed/', '/feed.xml', 'WordPress global RSS feed');
        $this->createRedirect('/rss', '/feed.xml', 'WordPress global RSS feed');

        $this->info("  Done. Generated redirect mappings for {$count} posts and feeds.");
    }

    private function createRedirect(string $fromPath, string $toUrl, string $notes): void
    {
        DB::table('redirects')->updateOrInsert(
            ['from_path' => $fromPath],
            [
                'to_url' => $toUrl,
                'status_code' => 301,
                'is_active' => true,
                'preserve_query' => true,
                'notes' => $notes,
                'hit_count' => 0,
                'created_by' => 1,
                'updated_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
