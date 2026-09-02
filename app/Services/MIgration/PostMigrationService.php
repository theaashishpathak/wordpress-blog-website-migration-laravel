<?php

declare(strict_types=1);

namespace App\Services\Migration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PostMigrationService
 *
 * Migrates WordPress posts into Rupantrix's `posts` and `post_translations` tables.
 * Applies lossless ContentTransformationService, RankMath SEO migration, views preservation,
 * and category/author/featured image mappings.
 */
class PostMigrationService extends BaseMigrationService
{
    private int $defaultLanguageId = 1;
    private int $chunkSize = 100;
    private ContentTransformationService $contentTransformer;
    private SeoMigrationService $seoMigrator;

    public function __construct(
        ContentTransformationService $contentTransformer,
        SeoMigrationService $seoMigrator
    ) {
        $this->contentTransformer = $contentTransformer;
        $this->seoMigrator = $seoMigrator;
    }

    public function run(): void
    {
        $lang = DB::table('languages')->where('code', 'en')->first();
        if ($lang) {
            $this->defaultLanguageId = $lang->id;
        }

        $total = $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'post')
            ->whereIn('post_status', ['publish', 'draft', 'pending', 'future'])
            ->count();

        $this->info("Found {$total} WordPress posts to migrate.");
        $this->info("Processing in chunks of {$this->chunkSize}...");

        $migrated = 0;

        $this->wp()
            ->table('wp_posts')
            ->where('post_type', 'post')
            ->whereIn('post_status', ['publish', 'draft', 'pending', 'future'])
            ->orderBy('ID')
            ->chunk($this->chunkSize, function ($wpPosts) use (&$migrated) {
                foreach ($wpPosts as $wpPost) {
                    $this->migratePost($wpPost);
                    $migrated++;
                }
                $this->info("  Progress: {$migrated} posts processed...");
            });

        $this->info("  Done. Posts migrated/updated: {$migrated}");
    }

    private function migratePost(object $wpPost): void
    {
        $postId = (int) $wpPost->ID;

        // 1. Featured Image
        $thumbnailId = $this->wp()
            ->table('wp_postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', '_thumbnail_id')
            ->value('meta_value');

        $featuredImageId = null;
        if ($thumbnailId) {
            $mediaExists = DB::table('media')->where('id', (int) $thumbnailId)->exists();
            $featuredImageId = $mediaExists ? (int) $thumbnailId : null;
        }

        // 2. Category
        $categoryId = $this->resolveCategory($postId);

        // 3. Author
        $authorExists = DB::table('users')->where('id', $wpPost->post_author)->exists();
        if (!$authorExists) {
            $firstAdmin = DB::table('users')->where('portal_type', 'admin')->value('id');
            $authorId = $firstAdmin ?? 1;
        } else {
            $authorId = (int) $wpPost->post_author;
        }

        // 4. Views Count
        $views = $this->wp()
            ->table('wp_postmeta')
            ->where('post_id', $postId)
            ->where('meta_key', 'penci_post_views_count')
            ->value('meta_value');
        $viewCount = $views ? (int) $views : 0;

        // 5. Status
        $status = match ($wpPost->post_status) {
            'publish' => 'published',
            'draft' => 'draft',
            'pending' => 'pending_review',
            'future' => 'scheduled',
            default => 'draft',
        };

        // 6. Insert / Update Post
        DB::table('posts')->updateOrInsert(
            ['id' => $postId],
            [
                'type' => 'post',
                'category_id' => $categoryId,
                'subcategory_id' => null,
                'author_id' => $authorId,
                'default_language_id' => $this->defaultLanguageId,
                'status' => $status,
                'visibility' => 'public',
                'is_featured' => false,
                'is_breaking' => false,
                'is_trending' => false,
                'is_editors_pick' => false,
                'is_sponsored' => false,
                'is_premium' => false,
                'allow_comments' => $wpPost->comment_status === 'open',
                'published_at' => $wpPost->post_date,
                'scheduled_at' => $wpPost->post_status === 'future' ? $wpPost->post_date : null,
                'breaking_expires_at' => null,
                'view_count' => $viewCount,
                'like_count' => 0,
                'share_count' => 0,
                'comment_count' => (int) ($wpPost->comment_count ?? 0),
                'featured_image_id' => $featuredImageId,
                'source_name' => null,
                'source_url' => null,
                'rss_source_id' => null,
                'created_by' => $authorId,
                'updated_by' => $authorId,
                'created_at' => $wpPost->post_date,
                'updated_at' => $wpPost->post_modified,
                'deleted_at' => null,
            ]
        );

        // 7. Transform HTML Content
        $cleanContent = $this->contentTransformer->transform($wpPost->post_content);
        $excerpt = $this->cleanExcerpt($wpPost->post_excerpt, $cleanContent);

        // 8. Insert / Update Post Translation
        $translationData = [
            'post_id' => $postId,
            'language_id' => $this->defaultLanguageId,
            'title' => $wpPost->post_title ?: '(Untitled)',
            'slug' => $wpPost->post_name ?: 'post-' . $postId,
            'excerpt' => $excerpt,
            'content' => $cleanContent,
            'created_at' => $wpPost->post_date,
            'updated_at' => $wpPost->post_modified,
        ];

        DB::table('post_translations')->updateOrInsert(
            [
                'post_id' => $postId,
                'language_id' => $this->defaultLanguageId,
            ],
            $translationData
        );

        // 9. Migrate SEO Metadata
        $this->seoMigrator->migrateEntitySeo('App\\Models\\Post', $postId, 'post');
    }

    private function resolveCategory(int $postId): ?int
    {
        $termId = $this->wp()
            ->table('wp_term_relationships as tr')
            ->join('wp_term_taxonomy as tt', 'tr.term_taxonomy_id', '=', 'tt.term_taxonomy_id')
            ->where('tr.object_id', $postId)
            ->where('tt.taxonomy', 'category')
            ->orderBy('tr.term_order')
            ->value('tt.term_id');

        if (!$termId) {
            return null;
        }

        $exists = DB::table('categories')->where('id', $termId)->exists();
        return $exists ? (int) $termId : null;
    }

    private function cleanExcerpt(?string $excerpt, string $content = ''): ?string
    {
        if ($excerpt && trim($excerpt) !== '') {
            $cleaned = str_replace(['[&hellip;]', '[...]', '&#8230;'], '', $excerpt);
            $result = trim(strip_tags($cleaned));
            if ($result !== '') {
                return $result;
            }
        }

        if ($content && trim($content) !== '') {
            $stripped = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
            if ($stripped !== '') {
                return \Illuminate\Support\Str::limit($stripped, 180, '...');
            }
        }

        return null;
    }
}
