<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SeoMigrationService
 *
 * Extracts SEO metadata from WordPress (RankMath & Yoast) and stores it in
 * both Rupantrix's polymorphic `seo_metas` table and the translation tables.
 */
class SeoMigrationService extends BaseMigrationService
{
    private int $defaultLanguageId = 1;

    public function run(): void
    {
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        }

        $this->info('Starting SEO metadata migration...');

        $this->migratePostsSeo();
        $this->migratePagesSeo();
        $this->migrateCategoriesSeo();
        $this->migrateTagsSeo();

        $this->info('SEO metadata migration completed.');
    }

    /**
     * Migrate SEO meta for all published posts.
     */
    public function migratePostsSeo(): void
    {
        $posts = DB::table('posts')->select('id')->get();
        $count = 0;

        foreach ($posts as $post) {
            $this->migrateEntitySeo('App\\Models\\Post', $post->id, 'post');
            $count++;
        }

        $this->info("  ✓ Migrated SEO meta for {$count} posts.");
    }

    /**
     * Migrate SEO meta for all pages.
     */
    public function migratePagesSeo(): void
    {
        $pages = DB::table('pages')->select('id')->get();
        $count = 0;

        foreach ($pages as $page) {
            $this->migrateEntitySeo('App\\Models\\Page', $page->id, 'page');
            $count++;
        }

        $this->info("  ✓ Migrated SEO meta for {$count} pages.");
    }

    /**
     * Migrate SEO meta for all categories.
     */
    public function migrateCategoriesSeo(): void
    {
        $categories = DB::table('categories')->select('id')->get();
        $count = 0;

        foreach ($categories as $cat) {
            $this->migrateTermSeo('App\\Models\\Category', $cat->id);
            $count++;
        }

        $this->info("  ✓ Migrated SEO meta for {$count} categories.");
    }

    /**
     * Migrate SEO meta for all tags.
     */
    public function migrateTagsSeo(): void
    {
        if (!Schema::hasTable('tags')) {
            return;
        }

        $tags = DB::table('tags')->select('id')->get();
        $count = 0;

        foreach ($tags as $tag) {
            $this->migrateTermSeo('App\\Models\\Tag', $tag->id);
            $count++;
        }

        $this->info("  ✓ Migrated SEO meta for {$count} tags.");
    }

    /**
     * Extract SEO meta for a post/page from wp_postmeta and save to seo_metas and post_translations.
     */
    public function migrateEntitySeo(string $morphClass, int $wpPostId, string $entityType = 'post'): void
    {
        $postMeta = $this->wp()->table('wp_postmeta')
            ->where('post_id', $wpPostId)
            ->pluck('meta_value', 'meta_key')
            ->toArray();

        // 1. Title & Description
        $title = $postMeta['rank_math_title']
            ?? $postMeta['_yoast_wpseo_title']
            ?? null;

        $description = $postMeta['rank_math_description']
            ?? $postMeta['_yoast_wpseo_metadesc']
            ?? null;

        // 2. Focus Keyword
        $focusKeyword = $postMeta['rank_math_focus_keyword']
            ?? $postMeta['_yoast_wpseo_focuskw']
            ?? null;

        // 3. Canonical URL
        $canonicalUrl = $postMeta['rank_math_canonical_url']
            ?? $postMeta['_yoast_wpseo_canonical']
            ?? null;

        // 4. Robots directive (index, follow, noindex, nofollow)
        $robots = $this->parseRobotsMeta($postMeta);

        // 5. Open Graph
        $ogTitle = $postMeta['rank_math_facebook_title'] ?? $postMeta['_yoast_wpseo_opengraph-title'] ?? $title;
        $ogDescription = $postMeta['rank_math_facebook_description'] ?? $postMeta['_yoast_wpseo_opengraph-description'] ?? $description;
        $ogImage = $postMeta['rank_math_facebook_image'] ?? $postMeta['_yoast_wpseo_opengraph-image'] ?? null;

        // 6. Twitter
        $twitterTitle = $postMeta['rank_math_twitter_title'] ?? $postMeta['_yoast_wpseo_twitter-title'] ?? $ogTitle;
        $twitterDescription = $postMeta['rank_math_twitter_description'] ?? $postMeta['_yoast_wpseo_twitter-description'] ?? $ogDescription;
        $twitterImage = $postMeta['rank_math_twitter_image'] ?? $postMeta['_yoast_wpseo_twitter-image'] ?? $ogImage;

        // 7. SEO Score
        $seoScore = isset($postMeta['rank_math_seo_score']) ? (int) $postMeta['rank_math_seo_score'] : null;

        // 8. Schema type
        $schemaType = 'Article';
        if (isset($postMeta['rank_math_rich_snippet'])) {
            $schemaType = match ($postMeta['rank_math_rich_snippet']) {
                'news' => 'NewsArticle',
                'blog' => 'BlogPosting',
                default => 'Article',
            };
        }

        // Upsert into seo_metas table
        if (Schema::hasTable('seo_metas')) {
            DB::table('seo_metas')->updateOrInsert(
                [
                    'seoable_type' => $morphClass,
                    'seoable_id' => $wpPostId,
                    'language_id' => $this->defaultLanguageId,
                ],
                [
                    'meta_title' => $title,
                    'meta_description' => $description,
                    'meta_keywords' => $focusKeyword,
                    'focus_keyword' => $focusKeyword,
                    'canonical_url' => $canonicalUrl,
                    'robots' => $robots,
                    'og_title' => $ogTitle,
                    'og_description' => $ogDescription,
                    'og_image' => $ogImage,
                    'twitter_title' => $twitterTitle,
                    'twitter_description' => $twitterDescription,
                    'twitter_image' => $twitterImage,
                    'schema_type' => $schemaType,
                    'schema_data' => null,
                    'seo_score' => $seoScore,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Also update post_translations or page_translations table
        if ($entityType === 'post' && Schema::hasTable('post_translations')) {
            $updateData = [];
            if ($title && Schema::hasColumn('post_translations', 'meta_title')) {
                $updateData['meta_title'] = $title;
            }
            if ($description && Schema::hasColumn('post_translations', 'meta_description')) {
                $updateData['meta_description'] = $description;
            }
            if ($focusKeyword && Schema::hasColumn('post_translations', 'focus_keyword')) {
                $updateData['focus_keyword'] = $focusKeyword;
            }
            if ($canonicalUrl && Schema::hasColumn('post_translations', 'canonical_url')) {
                $updateData['canonical_url'] = $canonicalUrl;
            }
            if ($ogImage && Schema::hasColumn('post_translations', 'og_image')) {
                $updateData['og_image'] = $ogImage;
            }
            if ($seoScore && Schema::hasColumn('post_translations', 'seo_score')) {
                $updateData['seo_score'] = $seoScore;
            }

            if (!empty($updateData)) {
                DB::table('post_translations')
                    ->where('post_id', $wpPostId)
                    ->where('language_id', $this->defaultLanguageId)
                    ->update($updateData);
            }
        } elseif ($entityType === 'page' && Schema::hasTable('page_translations')) {
            $updateData = [];
            if ($title && Schema::hasColumn('page_translations', 'meta_title')) {
                $updateData['meta_title'] = $title;
            }
            if ($description && Schema::hasColumn('page_translations', 'meta_description')) {
                $updateData['meta_description'] = $description;
            }
            if (!empty($updateData)) {
                DB::table('page_translations')
                    ->where('page_id', $wpPostId)
                    ->where('language_id', $this->defaultLanguageId)
                    ->update($updateData);
            }
        }
    }

    /**
     * Migrate SEO for category or tag terms from wp_termmeta.
     */
    public function migrateTermSeo(string $morphClass, int $termId): void
    {
        $termMeta = $this->wp()->table('wp_termmeta')
            ->where('term_id', $termId)
            ->pluck('meta_value', 'meta_key')
            ->toArray();

        $title = $termMeta['rank_math_title'] ?? $termMeta['wpseo_title'] ?? null;
        $description = $termMeta['rank_math_description'] ?? $termMeta['wpseo_desc'] ?? null;
        $robots = $this->parseRobotsMeta($termMeta);
        $canonicalUrl = $termMeta['rank_math_canonical_url'] ?? $termMeta['wpseo_canonical'] ?? null;

        if (Schema::hasTable('seo_metas')) {
            DB::table('seo_metas')->updateOrInsert(
                [
                    'seoable_type' => $morphClass,
                    'seoable_id' => $termId,
                    'language_id' => $this->defaultLanguageId,
                ],
                [
                    'meta_title' => $title,
                    'meta_description' => $description,
                    'meta_keywords' => null,
                    'focus_keyword' => null,
                    'canonical_url' => $canonicalUrl,
                    'robots' => $robots,
                    'og_title' => $title,
                    'og_description' => $description,
                    'og_image' => null,
                    'twitter_title' => $title,
                    'twitter_description' => $description,
                    'twitter_image' => null,
                    'schema_type' => 'CollectionPage',
                    'schema_data' => null,
                    'seo_score' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    /**
     * Parse robots directive from meta values.
     */
    private function parseRobotsMeta(array $meta): string
    {
        if (isset($meta['rank_math_robots'])) {
            $robotsVal = $meta['rank_math_robots'];
            if (is_string($robotsVal) && str_starts_with($robotsVal, 'a:')) {
                $unserialized = @unserialize($robotsVal);
                if (is_array($unserialized)) {
                    return implode(', ', $unserialized);
                }
            }
            if (is_string($robotsVal) && $robotsVal !== '') {
                return $robotsVal;
            }
        }

        if (isset($meta['_yoast_wpseo_meta-robots-noindex'])) {
            if ($meta['_yoast_wpseo_meta-robots-noindex'] == '1') {
                return 'noindex, follow';
            }
        }

        return 'index, follow';
    }
}
